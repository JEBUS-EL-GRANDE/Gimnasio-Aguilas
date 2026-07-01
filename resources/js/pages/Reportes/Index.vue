<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { FileText, Calendar, DollarSign, Award, Printer, AlertCircle } from 'lucide-vue-next';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reportes Financieros', href: '/admin/reportes' },
];

// Configurar fechas por defecto (del 1 del mes actual al día de hoy)
const hoy = new Date().toISOString().split('T')[0];
const primerDia = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];

const fechaDesde = ref(primerDia);
const fechaHasta = ref(hoy);
const tipoReporte = ref('General'); // General, Pagos, Membresias

const cargando = ref(false);
const errorMensaje = ref('');
const datosReporte = ref(null);

const consultarReporte = async () => {
  if (fechaDesde.value > fechaHasta.value) {
    errorMensaje.value = 'La fecha "Desde" no puede ser mayor a la fecha "Hasta" (RN-11).';
    return;
  }
  
  errorMensaje.value = '';
  cargando.value = true;
  
  try {
    const url = `/admin/reportes/financiero?fecha_desde=${fechaDesde.value}&fecha_hasta=${fechaHasta.value}`;
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.message || 'Error de consulta en el servidor.');
    }
    
    datosReporte.value = data;
  } catch (e) {
    errorMensaje.value = e.message || 'Error al obtener los datos del reporte (A2).';
    datosReporte.value = null;
  } finally {
    cargando.value = false;
  }
};

const exportarPDF = () => {
  window.print();
};

onMounted(() => {
  consultarReporte();
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 bg-white dark:bg-zinc-900 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 m-4 hover:shadow-lg transition-shadow duration-300 no-print">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-zinc-800/80">
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-800 dark:text-zinc-100 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
          </svg>
          <span>Generación de Reportes Financieros (CU-06)</span>
        </h1>
        <button 
          @click="exportarPDF" 
          v-if="datosReporte && datosReporte.total_recaudado > 0"
          class="bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white text-sm font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2"
        >
          <Printer class="h-4 w-4" />
          <span>Exportar a PDF / Imprimir</span>
        </button>
      </div>

      <!-- Sección de Filtros -->
      <div class="bg-zinc-50 dark:bg-zinc-800/30 p-5 rounded-xl border border-zinc-200/50 dark:border-zinc-800/60 mb-6">
        <h2 class="text-xs font-black uppercase text-orange-600 dark:text-orange-500 tracking-wider mb-4 flex items-center gap-1.5">
          <Calendar class="h-4 w-4 text-orange-500" />
          <span>Filtros de Búsqueda</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Fecha Desde *</label>
            <input 
              v-model="fechaDesde" 
              type="date" 
              required
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Fecha Hasta *</label>
            <input 
              v-model="fechaHasta" 
              type="date" 
              required
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Tipo de Reporte</label>
            <select 
              v-model="tipoReporte" 
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
            >
              <option value="General">General (Ingresos y Membresías)</option>
              <option value="Pagos">Detalle de Recaudación (Pagos)</option>
              <option value="Membresias">Venta de Planes (Membresías)</option>
            </select>
          </div>
          <div>
            <button 
              @click="consultarReporte" 
              :disabled="cargando"
              class="w-full bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm disabled:opacity-50"
            >
              {{ cargando ? 'Procesando...' : 'Aplicar Filtros' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Errores de Validación o de Consulta -->
      <div v-if="errorMensaje" class="bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 dark:border-red-600 p-4 rounded-r-lg mb-6 flex items-start gap-3 text-red-750 dark:text-red-400 font-semibold">
        <AlertCircle class="h-5 w-5 flex-shrink-0 text-red-500 dark:text-red-400" />
        <div>
          <span>Error de consulta:</span>
          <p class="text-sm font-normal mt-0.5">{{ errorMensaje }}</p>
        </div>
      </div>

      <!-- Resultados del Reporte -->
      <div v-if="datosReporte" class="space-y-6">
        
        <!-- Tarjetas de Indicadores Rápidos -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" v-if="tipoReporte === 'General' || tipoReporte === 'Pagos'">
          <div class="bg-orange-50 dark:bg-orange-950/20 border border-orange-200/50 dark:border-orange-900/30 rounded-xl p-5 flex items-center gap-4">
            <div class="bg-gradient-to-br from-orange-500 to-red-500 p-3 rounded-xl text-white shadow-md shadow-orange-500/20">
              <DollarSign class="h-6 w-6" />
            </div>
            <div>
              <span class="text-xs font-bold text-orange-800 dark:text-orange-300 uppercase tracking-wider block">Recaudación Total</span>
              <span class="text-2xl font-black text-orange-600 dark:text-orange-555">Bs. {{ parseFloat(datosReporte.total_recaudado).toFixed(2) }}</span>
            </div>
          </div>
          
          <div class="bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 flex items-center gap-4">
            <div class="bg-zinc-500 dark:bg-zinc-700 p-3 rounded-xl text-white shadow-md">
              <Award class="h-6 w-6" />
            </div>
            <div class="text-xs text-zinc-650 dark:text-zinc-400 font-bold uppercase tracking-wider space-y-0.5">
              <span class="block">Rango Seleccionado</span>
              <span class="text-sm font-black text-zinc-800 dark:text-zinc-200 block normal-case">Desde: {{ datosReporte.rango.desde }}</span>
              <span class="text-sm font-black text-zinc-800 dark:text-zinc-200 block normal-case">Hasta: {{ datosReporte.rango.hasta }}</span>
            </div>
          </div>
        </div>

        <!-- Flujo Alternativo: Sin Datos (A1) -->
        <div v-if="datosReporte.total_recaudado === 0 && datosReporte.membresias_vendidas.length === 0" class="text-center py-12 border border-gray-150 dark:border-zinc-800 rounded-xl bg-gray-50 dark:bg-zinc-800 text-gray-550 dark:text-zinc-400 font-medium">
          No hay registros en el rango de fechas seleccionado (A1).
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <!-- Tabla 1: Recaudación por métodos de pago (CU-06 / RF-08) -->
          <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl p-5 shadow-sm" v-if="tipoReporte === 'General' || tipoReporte === 'Pagos'">
            <h3 class="text-base font-extrabold text-gray-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
              <FileText class="h-5 w-5 text-orange-500" />
              <span>Ingresos por Método de Pago</span>
            </h3>
            <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-zinc-800">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-sm text-left">
                <thead class="bg-gray-50 dark:bg-zinc-800 font-bold text-gray-500 dark:text-zinc-400 uppercase text-xs tracking-wider">
                  <tr>
                    <th class="px-4 py-3">Método de Pago</th>
                    <th class="px-4 py-3 text-right">Monto Recaudado</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 text-gray-655 dark:text-zinc-300">
                  <tr v-for="item in datosReporte.detalle_ingresos" :key="item.metodo_pago" class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-zinc-200">{{ item.metodo_pago }}</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-800 dark:text-zinc-100">Bs. {{ parseFloat(item.total).toFixed(2) }}</td>
                  </tr>
                  <tr class="bg-gray-50/80 dark:bg-zinc-800/80 font-bold text-gray-900 dark:text-zinc-100">
                    <td class="px-4 py-3">Total General</td>
                    <td class="px-4 py-3 text-right text-orange-600 dark:text-orange-455">Bs. {{ parseFloat(datosReporte.total_recaudado).toFixed(2) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tabla 2: Membresías vendidas agrupadas por plan -->
          <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl p-5 shadow-sm" v-if="tipoReporte === 'General' || tipoReporte === 'Membresias'">
            <h3 class="text-base font-extrabold text-gray-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
              <Award class="h-5 w-5 text-orange-500" />
              <span>Membresías Adquiridas por Tipo</span>
            </h3>
            <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-zinc-800">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-sm text-left">
                <thead class="bg-gray-50 dark:bg-zinc-800 font-bold text-gray-500 dark:text-zinc-400 uppercase text-xs tracking-wider">
                  <tr>
                    <th class="px-4 py-3">Plan de Membresía</th>
                    <th class="px-4 py-3 text-center">Cantidad Vendida</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 text-gray-650 dark:text-zinc-300">
                  <tr v-for="item in datosReporte.membresias_vendidas" :key="item.nombre_membresia" class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-zinc-200">{{ item.nombre_membresia }}</td>
                    <td class="px-4 py-3 text-center font-bold text-gray-850 dark:text-zinc-100">{{ item.cantidad }}</td>
                  </tr>
                  <tr v-if="datosReporte.membresias_vendidas.length === 0">
                    <td colspan="2" class="px-4 py-3 text-center text-gray-400 dark:text-zinc-500 italic">No hay membresías registradas en este período.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tabla 3: Detalle de Transferencias Bancarias -->
          <div
            v-if="(tipoReporte === 'General' || tipoReporte === 'Pagos') && datosReporte.transferencias && datosReporte.transferencias.length > 0"
            class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl p-5 shadow-sm md:col-span-2"
          >
            <h3 class="text-base font-extrabold text-gray-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
              <FileText class="h-5 w-5 text-blue-500" />
              <span>Detalle de Transferencias Bancarias</span>
              <span class="ml-auto text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">
                {{ datosReporte.transferencias.length }} registro(s)
              </span>
            </h3>
            <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-zinc-800">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-sm text-left">
                <thead class="bg-gray-50 dark:bg-zinc-800 font-bold text-gray-500 dark:text-zinc-400 uppercase text-xs tracking-wider">
                  <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Banco Origen</th>
                    <th class="px-4 py-3">Banco Destino</th>
                    <th class="px-4 py-3">Código Transacción</th>
                    <th class="px-4 py-3 text-right">Monto</th>
                    <th class="px-4 py-3 text-center">Comprobante</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 text-gray-655 dark:text-zinc-300">
                  <tr v-for="(tr, i) in datosReporte.transferencias" :key="i" class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-zinc-200">{{ tr.fecha }}</td>
                    <td class="px-4 py-3">{{ tr.banco_origen }}</td>
                    <td class="px-4 py-3">{{ tr.banco_destino }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ tr.codigo_transaccion }}</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-800 dark:text-zinc-100">Bs. {{ parseFloat(tr.monto).toFixed(2) }}</td>
                    <td class="px-4 py-3 text-center">
                      <a v-if="tr.comprobante_url" :href="tr.comprobante_url" target="_blank"
                        class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">Ver</a>
                      <span v-else class="text-gray-400 dark:text-zinc-500 text-xs italic">N/D</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Vista de impresión optimizada (se muestra al imprimir) -->
    <div class="print-only p-8 text-black bg-white">
      <div class="border-b-2 border-black pb-4 mb-6">
        <h1 class="text-2xl font-bold uppercase">Reporte de Ingresos del Sistema</h1>
        <p class="text-sm text-gray-600">Generado el: {{ new Date().toLocaleString() }}</p>
      </div>

      <div class="mb-6">
        <h2 class="text-lg font-bold">Resumen de Parámetros</h2>
        <p class="text-sm">Rango de fechas: Desde <strong>{{ fechaDesde }}</strong> Hasta <strong>{{ fechaHasta }}</strong></p>
        <p class="text-sm">Tipo de Reporte: <strong>{{ tipoReporte }}</strong></p>
      </div>

      <div v-if="datosReporte" class="space-y-6">
        <div class="border p-4 rounded mb-6 flex justify-between items-center bg-gray-50">
          <span class="text-lg font-bold">RECAUDACIÓN TOTAL ACUMULADA:</span>
          <span class="text-2xl font-black text-emerald-800">Bs. {{ parseFloat(datosReporte.total_recaudado).toFixed(2) }}</span>
        </div>

        <div class="space-y-6">
          <div v-if="tipoReporte === 'General' || tipoReporte === 'Pagos'">
            <h3 class="text-base font-bold mb-2">Ingresos por Método de Pago</h3>
            <table class="w-full border-collapse border border-gray-300 text-sm">
              <thead>
                <tr class="bg-gray-100">
                  <th class="border border-gray-300 px-4 py-2 text-left">Método de Pago</th>
                  <th class="border border-gray-300 px-4 py-2 text-right">Monto Recaudado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in datosReporte.detalle_ingresos" :key="item.metodo_pago">
                  <td class="border border-gray-300 px-4 py-2">{{ item.metodo_pago }}</td>
                  <td class="border border-gray-300 px-4 py-2 text-right">Bs. {{ parseFloat(item.total).toFixed(2) }}</td>
                </tr>
                <tr class="font-bold">
                  <td class="border border-gray-300 px-4 py-2">Total Recaudado</td>
                  <td class="border border-gray-300 px-4 py-2 text-right text-emerald-800">Bs. {{ parseFloat(datosReporte.total_recaudado).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="tipoReporte === 'General' || tipoReporte === 'Membresias'">
            <h3 class="text-base font-bold mb-2 mt-4">Membresías Adquiridas por Tipo</h3>
            <table class="w-full border-collapse border border-gray-300 text-sm">
              <thead>
                <tr class="bg-gray-100">
                  <th class="border border-gray-300 px-4 py-2 text-left">Plan de Membresía</th>
                  <th class="border border-gray-300 px-4 py-2 text-center">Cantidad Vendida</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in datosReporte.membresias_vendidas" :key="item.nombre_membresia">
                  <td class="border border-gray-300 px-4 py-2">{{ item.nombre_membresia }}</td>
                  <td class="border border-gray-300 px-4 py-2 text-center font-bold">{{ item.cantidad }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="(tipoReporte === 'General' || tipoReporte === 'Pagos') && datosReporte.transferencias && datosReporte.transferencias.length > 0">
            <h3 class="text-base font-bold mb-2 mt-4">Detalle de Transferencias Bancarias</h3>
            <table class="w-full border-collapse border border-gray-300 text-sm">
              <thead>
                <tr class="bg-gray-100">
                  <th class="border border-gray-300 px-4 py-2 text-left">Fecha</th>
                  <th class="border border-gray-300 px-4 py-2 text-left">Banco Origen</th>
                  <th class="border border-gray-300 px-4 py-2 text-left">Banco Destino</th>
                  <th class="border border-gray-300 px-4 py-2 text-left">Código Transacción</th>
                  <th class="border border-gray-300 px-4 py-2 text-right">Monto</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(tr, i) in datosReporte.transferencias" :key="i">
                  <td class="border border-gray-300 px-4 py-2">{{ tr.fecha }}</td>
                  <td class="border border-gray-300 px-4 py-2">{{ tr.banco_origen }}</td>
                  <td class="border border-gray-300 px-4 py-2">{{ tr.banco_destino }}</td>
                  <td class="border border-gray-300 px-4 py-2 font-mono text-xs">{{ tr.codigo_transaccion }}</td>
                  <td class="border border-gray-300 px-4 py-2 text-right font-bold">Bs. {{ parseFloat(tr.monto).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
@media print {
  .no-print {
    display: none !important;
  }
  .print-only {
    display: block !important;
  }
  body {
    background: white !important;
    color: black !important;
  }
}
@media screen {
  .print-only {
    display: none !important;
  }
}
</style>
