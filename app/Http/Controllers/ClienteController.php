<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClienteController extends Controller
{
    // Consultar clientes por criterio de búsqueda (CI o Nombre) - CU-03
    public function index(Request $request)
    {
        $query = Cliente::with('persona');

        if ($request->has('buscar')) {
            $search = $request->input('buscar');
            $query->whereHas('persona', function ($q) use ($search) {
                $q->where('ci', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%");
            });
        }

        $clientes = $query->paginate(15)->withQueryString();

        return \Inertia\Inertia::render('Clientes/Index', [
            'clientes'    => $clientes->items(),
            'paginacion'  => [
                'paginaActual' => $clientes->currentPage(),
                'ultimaPagina' => $clientes->lastPage(),
                'total'        => $clientes->total(),
                'desde'        => $clientes->firstItem() ?? 0,
                'hasta'        => $clientes->lastItem() ?? 0,
            ],
        ]);
    }

    // Registrar nuevo cliente aplicando reglas transaccionales - CU-02
    public function store(Request $request)
    {
        // Flujo Alternativo A2: Datos incompletos (Validación de campos obligatorios)
        $request->validate([
            'ci' => ['required', 'string'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string'],
            'direccion' => ['nullable', 'string'],
            'sexo' => ['required', 'string', 'max:1'],
            'fecha_limite' => ['nullable', 'date'],
        ]);

        // Regla de Negocio RN-01 / Flujo Alternativo A3: Cliente ya registrado (CI duplicado)
        $personaExistente = Persona::where('ci', $request->ci)->first();
        if ($personaExistente) {
            $clienteExistente = Cliente::where('id_persona', $personaExistente->id_persona)->first();
            if ($clienteExistente) {
                throw ValidationException::withMessages([
                    'ci' => "Cliente ya existe: {$personaExistente->nombre} {$personaExistente->apellido} (CI: {$personaExistente->ci})",
                ]);
            }
        }

        // Ejecutar inserción bajo transacción segura para asegurar integridad de datos
        DB::beginTransaction();
        try {
            // Crear la Persona
            $persona = Persona::create([
                'ci' => $request->ci,
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'sexo' => $request->sexo,
                'fecha_registro' => now()->toDateString(),
            ]);

            // Crear el Cliente asociado
            $cliente = Cliente::create([
                'id_persona' => $persona->id_persona,
                'fecha_limite' => $request->input('fecha_limite', now()->toDateString()),
                'estado' => true,
            ]);

            DB::commit();
            return redirect()->route('clientes.index')->with('success', 'Cliente registrado con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' => 'Error al guardar el cliente: ' . $e->getMessage(),
            ]);
        }
    }

    // Editar información de un cliente existente - CU-02 (Flujo Alternativo A1)
    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $persona = Persona::findOrFail($cliente->id_persona);

        $request->validate([
            'ci' => ['required', 'string'],
            'nombre' => ['required', 'string'],
            'apellido' => ['required', 'string'],
            'sexo' => ['required', 'string'],
            'fecha_limite' => ['nullable', 'date'],
        ]);

        // Validar que el nuevo CI no pertenezca a otra persona diferente
        $ciDuplicado = Persona::where('ci', $request->ci)->where('id_persona', '!=', $persona->id_persona)->exists();
        if ($ciDuplicado) {
            throw ValidationException::withMessages([
                'ci' => 'El CI ingresado ya se encuentra registrado por otro cliente.',
            ]);
        }

        DB::beginTransaction();
        try {
            $persona->update($request->only(['ci', 'nombre', 'apellido', 'telefono', 'direccion', 'sexo']));
            $cliente->update([
                'fecha_limite' => $request->input('fecha_limite', $cliente->fecha_limite),
                'estado' => $request->input('estado', $cliente->estado),
            ]);

            DB::commit();
            return redirect()->route('clientes.index')->with('success', 'Cambios guardados con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' => 'Error al actualizar los datos.',
            ]);
        }
    }

    // Buscar clientes y sus estados de membresía para el Dashboard
    public function buscarClienteMembresia(Request $request): \Illuminate\Http\JsonResponse
    {
        $buscar = $request->input('buscar');
        if (empty($buscar)) {
            return response()->json([]);
        }

        $clientes = Cliente::with(['persona', 'membresias.tipoMembresia'])
            ->whereHas('persona', function ($q) use ($buscar) {
                $q->where('ci', 'like', "%{$buscar}%")
                  ->orWhere('nombre', 'like', "%{$buscar}%")
                  ->orWhere('apellido', 'like', "%{$buscar}%");
            })
            ->get()
            ->map(function ($cliente) {
                // Obtener la última membresía registrada para este cliente
                $ultimaMembresia = $cliente->membresias->sortByDesc('fecha_vencimiento')->first();
                
                $hoy = now()->toDateString();
                $esActiva = false;
                if ($cliente->fecha_limite && $cliente->fecha_limite >= $hoy) {
                    $esActiva = true;
                }

                return [
                    'id_cliente' => $cliente->id_cliente,
                    'nombre' => $cliente->persona->nombre . ' ' . $cliente->persona->apellido,
                    'ci' => $cliente->persona->ci,
                    'fecha_limite' => $cliente->fecha_limite,
                    'estado' => $cliente->estado,
                    'es_activa' => $esActiva,
                    'ultima_membresia' => $ultimaMembresia ? [
                        'nombre_membresia' => $ultimaMembresia->tipoMembresia->nombre_membresia ?? 'N/A',
                        'fecha_inicio' => $ultimaMembresia->fecha_inicio,
                        'fecha_vencimiento' => $ultimaMembresia->fecha_vencimiento,
                    ] : null
                ];
            });

        return response()->json($clientes);
    }
}