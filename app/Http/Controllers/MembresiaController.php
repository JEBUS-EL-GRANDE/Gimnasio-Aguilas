<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\TipoMembresia;
use App\Models\Promocion;
use App\Models\Membresia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MembresiaController extends Controller
{
    // Mostrar el formulario de registro de membresías y pagos
    public function index()
    {
        // Traemos a los clientes con su información personal y el conteo de membresías para validar
        $clientes = Cliente::with('persona')->withCount('membresias')->get();
        // Traemos solo los tipos de membresía activos
        $tiposMembresia = TipoMembresia::where('estado', true)->get();
        // Traemos solo las promociones activas y vigentes hoy
        $promociones = Promocion::where('estado', true)
            ->where('fecha_inicio', '<=', now()->toDateString())
            ->where('fecha_fin', '>=', now()->toDateString())
            ->get();
        
        return \Inertia\Inertia::render('Membresias/Index', [
            'clientes' => $clientes,
            'tiposMembresia' => $tiposMembresia,
            'promociones' => $promociones
        ]);
    }

    // Inicializa y registra la asignación de una membresía - CU-04
    public function store(Request $request)
    {
        // Validación inicial de datos requeridos
        $request->validate([
            'id_cliente' => ['required', 'integer'],
            'id_tipo_membresia' => ['required', 'integer'],
            'id_promocion' => ['nullable', 'integer'],
            'fecha_inicio' => ['required', 'date'],
            'metodo_pago' => ['required', 'string'], // 'Efectivo' o 'Transferencia/QR'
            'cantidad_periodos' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        // Precondición / Flujo Alternativo A1: El cliente debe existir
        $cliente = Cliente::find($request->id_cliente);
        if (!$cliente) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_cliente' => 'Cliente inexistente.'
            ]);
        }

        // Regla de Negocio: Validar membresía duplicada y pago adelantado
        $pagoAdelantado = filter_var($request->input('pago_adelantado'), FILTER_VALIDATE_BOOLEAN);
        $fechaInicio = Carbon::parse($request->fecha_inicio);

        // Obtener la membresía activa más reciente si existe
        $membresiaActiva = Membresia::where('id_cliente', $cliente->id_cliente)
            ->where('fecha_vencimiento', '>=', now()->toDateString())
            ->orderBy('fecha_vencimiento', 'desc')
            ->first();

        if ($membresiaActiva) {
            if (!$pagoAdelantado) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'id_cliente' => 'El cliente ya cuenta con una membresía activa que vence el ' . Carbon::parse($membresiaActiva->fecha_vencimiento)->format('Y-m-d') . '. Marque la opción de pago adelantado para registrar una nueva membresía.'
                ]);
            }
            // Pago adelantado: la nueva membresía se inicia el día después del vencimiento actual
            $fechaInicio = Carbon::parse($membresiaActiva->fecha_vencimiento)->addDay();
        }

        // Precondición: Debe existir el tipo de membresía y estar habilitado (Corrección del Ing.)
        $tipoMembresia = TipoMembresia::find($request->id_tipo_membresia);
        if (!$tipoMembresia || !$tipoMembresia->estado) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_tipo_membresia' => 'El tipo de membresía no existe o está inactivo.'
            ]);
        }

        // Establecer monto base según los períodos a pagar
        $cantidadPeriodos = (int) $request->input('cantidad_periodos', 1);
        if ($cantidadPeriodos < 1) {
            $cantidadPeriodos = 1;
        }
        $montoFinal = $tipoMembresia->precio * $cantidadPeriodos;

        // Flujo Alternativo A2: Promoción aplicada
        if ($request->id_promocion) {
            $promocion = Promocion::find($request->id_promocion);
            
            // Regla de Negocio RN-11: Solo promociones activas y en rango de fechas vigente
            if ($promocion && $promocion->estado && Carbon::now()->between($promocion->fecha_inicio, $promocion->fecha_fin)) {
                $descuento = ($montoFinal * $promocion->porcentaje_descuento) / 100;
                $montoFinal = $montoFinal - $descuento;
            } else {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'id_promocion' => 'La promoción seleccionada no se encuentra vigente.'
                ]);
            }
        }

        // Calcula de forma exacta la fecha de vencimiento según DuracionDias * cantidad_periodos
        $fechaVencimiento = $fechaInicio->copy()->addDays($tipoMembresia->duracion_dias * $cantidadPeriodos);

        // Iniciamos flujo transaccional seguro (Membresía + Pago Obligatorio <<include>>)
        DB::beginTransaction();
        try {
            // Guardar la membresía temporalmente en la base de datos
            $membresia = Membresia::create([
                'id_cliente' => $cliente->id_cliente,
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'id_promocion' => $request->id_promocion,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_vencimiento' => $fechaVencimiento->toDateString(),
            ]);

            // Redirección interna obligatoria para procesar el pago (<<include>> Registrar Pago)
            $pagoRequest = new Request([
                'id_membresia' => $membresia->id_membresia,
                'monto' => $montoFinal,
                'metodo_pago' => $request->metodo_pago,
                // Datos para el flujo alternativo de transferencia si aplica
                'banco_origen' => $request->banco_origen,
                'banco_destino' => $request->banco_destino,
                'cuenta_destino' => $request->cuenta_destino,
                'codigo_transaccion' => $request->codigo_transaccion,
                'comprobante_foto' => $request->file('comprobante_foto')
            ]);

            if ($request->hasFile('comprobante_foto')) {
                $pagoRequest->files->set('comprobante_foto', $request->file('comprobante_foto'));
            }

            $pagoController = new PagoController();
            $pagoResponse = $pagoController->registrarPagoInterno($pagoRequest);

            if ($pagoResponse['status'] !== 'success') {
                throw new \Exception($pagoResponse['message']);
            }

            // Postcondición: Si el pago es exitoso, actualizamos la fecha límite del cliente global
            $cliente->update([
                'fecha_limite' => $fechaVencimiento->toDateString(),
                'estado' => true
            ]);

            DB::commit();
            return redirect()->route('membresias.index')->with('success', 'Membresía registrada con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_cliente' => 'Transacción cancelada: ' . $e->getMessage()
            ]);
        }
    }
}