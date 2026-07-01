<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Membresia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    // Renderiza la interfaz de reportes (CU-06)
    public function index()
    {
        return \Inertia\Inertia::render('Reportes/Index');
    }

    // Generar reporte financiero y estadístico por rango de fechas
    public function generarReporteFinanciero(Request $request)
    {
        $request->validate([
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $desde = $request->fecha_desde;
        $hasta = $request->fecha_hasta;

        // 1. Recaudación total y agrupación por método de pago
        $ingresos = Pago::whereBetween('fecha_pago', [$desde, $hasta])
            ->select('metodo_pago', DB::raw('SUM(monto) as total'))
            ->groupBy('metodo_pago')
            ->get();

        $granTotal = $ingresos->sum('total');

        // 2. Cantidad de membresías vendidas agrupadas por el tipo
        $membresiasVendidas = Membresia::whereBetween('fecha_inicio', [$desde, $hasta])
            ->join('tipo_membresias', 'membresias.id_tipo_membresia', '=', 'tipo_membresias.id_tipo_membresia')
            ->select('tipo_membresias.nombre_membresia', DB::raw('COUNT(membresias.id_membresia) as cantidad'))
            ->groupBy('tipo_membresias.nombre_membresia')
            ->get();

        // 3. Detalle completo de pagos por transferencia en el rango
        $transferencias = Pago::with('transferencia')
            ->whereBetween('fecha_pago', [$desde, $hasta])
            ->where('metodo_pago', 'Transferencia')
            ->get()
            ->map(fn ($pago) => [
                'fecha'              => $pago->fecha_pago,
                'monto'              => $pago->monto,
                'banco_origen'       => $pago->transferencia?->banco_origen ?? '-',
                'banco_destino'      => $pago->transferencia?->banco_destino ?? '-',
                'codigo_transaccion' => $pago->transferencia?->codigo_transaccion ?? '-',
                'comprobante_url'    => $pago->transferencia?->comprobante_foto
                    ? asset('storage/'.$pago->transferencia->comprobante_foto)
                    : null,
            ]);

        return response()->json([
            'rango'               => ['desde' => $desde, 'hasta' => $hasta],
            'total_recaudado'     => $granTotal,
            'detalle_ingresos'    => $ingresos,
            'membresias_vendidas' => $membresiasVendidas,
            'transferencias'      => $transferencias,
        ], 200);
    }
}