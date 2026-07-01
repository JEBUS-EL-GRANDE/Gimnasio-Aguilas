<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage, Link } from '@inertiajs/vue3';

const page = usePage();
const userRole = computed(() => page.props.auth.user?.rol);

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
];

const filtroReporte = ref({
  fecha_desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0], // Primer día del mes
  fecha_hasta: new Date().toISOString().split('T')[0] // Hoy
});

const reporteDatos = ref(null);
const procesandoBackup = ref(false);
const urlDescargaBackup = ref('');

// Consulta asíncrona usando Axios para no refrescar toda la interfaz de Inertia
const consultarEstadisticas = async () => {
  try {
    const respuesta = await axios.get('/admin/reportes/financiero', { params: filtroReporte.value });
    reporteDatos.value = respuesta.data;
  } catch (error) {
    alert('Error al compilar los reportes del periodo.');
  }
};

// Dispara la ejecución del comando pg_dump en el servidor de Laravel
const ejecutarRespaldoBaseDatos = async () => {
  if (confirm('¿Desea realizar el respaldo manual de la base de datos ahora mismo?')) {
    procesandoBackup.value = true;
    urlDescargaBackup.value = '';
    try {
      const respuesta = await axios.post('/admin/backup/crear');
      alert(respuesta.data.message);
      urlDescargaBackup.value = respuesta.data.descargar_url;
    } catch (error) {
      alert('Error crítico: El comando del sistema falló. Verifique los privilegios de PostgreSQL.');
    } finally {
      procesandoBackup.value = false;
    }
  }
};

// Variables reactivas para el buscador rápido de membresías
const busquedaCliente = ref('');
const resultadosClientes = ref([]);
const buscando = ref(false);

// Realiza una petición asíncrona para buscar clientes y verificar el estado de su membresía
const buscarMembresiaCliente = async () => {
  if (!busquedaCliente.value.trim()) {
    resultadosClientes.value = [];
    return;
  }
  buscando.value = true;
  try {
    const respuesta = await axios.get('/dashboard/buscar-cliente', { params: { buscar: busquedaCliente.value } });
    resultadosClientes.value = respuesta.data;
  } catch (error) {
    alert('Error al buscar el cliente y su membresía.');
  } finally {
    buscando.value = false;
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      
      <!-- Banner de bienvenida con gradiente premium energético de Gimnasio -->
      <div class="relative overflow-hidden bg-gradient-to-r from-orange-600 to-red-700 text-white p-8 rounded-xl shadow-lg border border-orange-500/20 hover:shadow-xl transition-shadow duration-300 flex justify-between items-center">
        <div class="relative z-10">
          <h1 class="text-3xl font-extrabold tracking-tight">Panel de Control General</h1>
          <p class="text-orange-100 text-sm mt-2 font-medium">Bienvenido al sistema administrativo del gimnasio. Perfil activo: <span class="bg-white/20 px-2 py-0.5 rounded text-white font-bold text-xs uppercase">{{ userRole }}</span></p>
        </div>
        <div class="relative z-10 hidden sm:block bg-zinc-950 p-1.5 rounded-xl border border-white/10 shadow-md">
          <img src="/logo-gym.png" class="h-16 w-16 object-contain rounded-lg" alt="Eagle Fitness Logo" />
        </div>
        <!-- Elemento de fondo abstracto y moderno -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- Panel de Consulta Rápida de Membresías (para ambos roles) -->
      <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 space-y-4 hover:shadow-lg transition-shadow duration-300">
        <h2 class="text-lg font-bold text-gray-800 dark:text-zinc-100 border-b border-gray-100 dark:border-zinc-800 pb-3 flex items-center space-x-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <span>Consulta Rápida de Membresías</span>
        </h2>
        
        <div class="flex flex-col sm:flex-row gap-3">
          <input 
            v-model="busquedaCliente" 
            type="text" 
            placeholder="Buscar cliente por Nombre, Apellido o CI..." 
            @keyup.enter="buscarMembresiaCliente"
            class="flex-1 border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
          />
          <button 
            @click="buscarMembresiaCliente"
            :disabled="buscando"
            class="bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-1.5 text-sm disabled:opacity-50"
          >
            <span v-if="buscando">Buscando...</span>
            <span v-else class="flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              Buscar
            </span>
          </button>
        </div>

        <!-- Resultados de la búsqueda -->
        <div v-if="resultadosClientes.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
          <div 
            v-for="cliente in resultadosClientes" 
            :key="cliente.id_cliente" 
            class="p-4 rounded-xl border transition-all duration-300"
            :class="cliente.es_activa 
              ? 'bg-emerald-50/50 dark:bg-emerald-950/10 border-emerald-250/60 dark:border-emerald-900/30' 
              : 'bg-zinc-50 dark:bg-zinc-800/30 border-zinc-200/50 dark:border-zinc-800/60'"
          >
            <div class="flex justify-between items-start">
              <div>
                <h3 class="font-extrabold text-sm text-gray-800 dark:text-zinc-150">{{ cliente.nombre }}</h3>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">CI: {{ cliente.ci }}</p>
              </div>
              <div>
                <span 
                  class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider"
                  :class="cliente.es_activa 
                    ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-450' 
                    : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400'"
                >
                  {{ cliente.es_activa ? 'Activa' : 'Inactiva' }}
                </span>
              </div>
            </div>

            <div class="mt-3 pt-3 border-t border-dashed border-zinc-200/60 dark:border-zinc-800/60 text-xs space-y-1 text-gray-650 dark:text-zinc-350 font-medium">
              <div v-if="cliente.ultima_membresia">
                <p><strong>Plan Reciente:</strong> {{ cliente.ultima_membresia.nombre_membresia }}</p>
                <p><strong>Periodo:</strong> {{ cliente.ultima_membresia.fecha_inicio }} al {{ cliente.ultima_membresia.fecha_vencimiento }}</p>
              </div>
              <div v-else class="text-gray-400 dark:text-zinc-500 italic">
                Sin registros de membresías previas.
              </div>
              
              <!-- Mostrar fecha límite destacada -->
              <p class="pt-1">
                <span v-if="cliente.es_activa" class="text-emerald-600 dark:text-emerald-400 font-bold">
                  🟢 Vence el: {{ cliente.fecha_limite }}
                </span>
                <span v-else-if="cliente.fecha_limite" class="text-red-650 dark:text-red-400 font-bold">
                  🔴 Venció el: {{ cliente.fecha_limite }}
                </span>
                <span v-else class="text-gray-500 dark:text-zinc-500 font-bold">
                  ⚪ Sin fecha de vencimiento registrada
                </span>
              </p>
            </div>
          </div>
        </div>

        <div v-else-if="busquedaCliente.trim() && !buscando" class="text-xs text-gray-405 dark:text-zinc-500 italic pt-2">
          No se encontraron clientes que coincidan con la búsqueda.
        </div>
      </div>

      <!-- Si el usuario es Administrador, muestra los paneles de control financieros y de seguridad -->
      <div v-if="userRole === 'Administrador'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 md:col-span-2 space-y-4 hover:scale-[1.01] hover:shadow-lg transition-all duration-300">
          <h2 class="text-lg font-bold text-gray-800 dark:text-zinc-100 border-b border-gray-100 dark:border-zinc-800 pb-3 flex items-center space-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
            </svg>
            <span>Auditoría Financiera y Membresías (CU-06)</span>
          </h2>
          
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end pt-2">
            <div>
              <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Fecha Inicial</label>
              <input v-model="filtroReporte.fecha_desde" type="date" class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Fecha Límite</label>
              <input v-model="filtroReporte.fecha_hasta" type="date" class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none" />
            </div>
            <button 
              @click="consultarEstadisticas"
              class="w-full bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white text-sm font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
            >
              Consultar Datos
            </button>
          </div>

          <div v-if="reporteDatos" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
            <div class="bg-orange-50 dark:bg-orange-950/20 p-5 rounded-lg border border-orange-200/50 dark:border-orange-900/30">
              <span class="text-xs font-bold text-orange-800 dark:text-orange-300 uppercase tracking-wider block">Total Ingresos Recaudados</span>
              <span class="text-3xl font-black text-orange-600 dark:text-orange-500 block mt-2">Bs. {{ parseFloat(reporteDatos.total_recaudado).toFixed(2) }}</span>
            </div>
            <div class="bg-zinc-50 dark:bg-zinc-800/30 p-5 rounded-lg border border-zinc-200/50 dark:border-zinc-800/60">
              <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider block">Membresías Vendidas</span>
              <div class="mt-2 space-y-1.5 max-h-24 overflow-y-auto text-xs font-medium text-zinc-600 dark:text-zinc-400">
                <div v-for="item in reporteDatos.membresias_vendidas" :key="item.nombre_membresia" class="flex justify-between border-b border-dashed border-zinc-200 dark:border-zinc-800/80 pb-1">
                  <span>• {{ item.nombre_membresia }}:</span>
                  <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ item.cantidad }} registros</span>
                </div>
                <div v-if="reporteDatos.membresias_vendidas.length === 0" class="text-gray-400 dark:text-zinc-500 italic">Sin movimientos en este periodo.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 flex flex-col justify-between hover:scale-[1.01] hover:shadow-lg transition-all duration-300">
          <div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-zinc-100 border-b border-gray-100 dark:border-zinc-800 pb-3 flex items-center space-x-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              <span>Seguridad del Sistema (CU-11)</span>
            </h2>
            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-3 leading-relaxed">
              Para cumplir con la directiva **RNF-07**, el administrador puede forzar la exportación manual instantánea de toda la información de PostgreSQL.
            </p>
            <div class="bg-amber-50/50 dark:bg-amber-950/10 border border-amber-200/50 dark:border-amber-900/20 p-3.5 rounded-lg mt-3 text-xs text-amber-800 dark:text-amber-400 font-medium">
              El archivo generado contendrá la estructura de tablas, herencias de personas, clientes, pagos y credenciales de acceso.
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 dark:border-zinc-800 mt-4 space-y-2">
            <button 
              @click="ejecutarRespaldoBaseDatos"
              :disabled="procesandoBackup"
              class="w-full bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center text-sm disabled:opacity-50"
            >
              {{ procesandoBackup ? 'Estructurando Respaldo...' : 'Generar Backup Manual (.SQL)' }}
            </button>
            
            <a 
              v-if="urlDescargaBackup" 
              :href="urlDescargaBackup" 
              download
              class="w-full bg-orange-50 dark:bg-orange-950/20 hover:bg-orange-100 dark:hover:bg-orange-950/40 text-orange-700 dark:text-orange-400 font-bold py-2.5 px-4 rounded-lg block text-center text-xs border border-orange-200 dark:border-orange-900/30 transition-colors duration-200"
            >
              📥 Descargar Archivo de Respaldo
            </a>
          </div>
        </div>

      </div>

      <!-- Si el usuario es Recepcionista, muestra enlaces rápidos y bienvenida operativa -->
      <div v-else class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 space-y-4 hover:scale-[1.01] hover:shadow-lg transition-all duration-300">
        <h2 class="text-lg font-bold text-gray-800 dark:text-zinc-100 border-b border-gray-100 dark:border-zinc-800 pb-3 flex items-center space-x-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span>Atención al Cliente y Registro</span>
        </h2>
        <p class="text-sm text-gray-600 dark:text-zinc-400 leading-relaxed">
          Has iniciado sesión como **Recepcionista**. Desde tu panel de control puedes gestionar la información de los clientes, sus membresías y pagos.
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
          <Link href="/clientes" class="flex items-center space-x-4 p-5 bg-gradient-to-br from-orange-50/50 to-orange-100/30 dark:from-zinc-800 dark:to-zinc-800/40 border border-orange-100/50 dark:border-zinc-700/50 rounded-xl hover:from-orange-50 dark:hover:from-zinc-800/80 hover:shadow-md transition-all duration-200">
            <div class="p-3 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg shadow-md shadow-orange-500/20">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <h3 class="font-bold text-gray-800 dark:text-zinc-100 text-sm">Gestionar Clientes</h3>
              <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Registrar clientes nuevos, buscar en el padrón o actualizar datos existentes.</p>
            </div>
          </Link>

          <Link href="/membresias" class="flex items-center space-x-4 p-5 bg-gradient-to-br from-emerald-50/50 to-emerald-100/30 dark:from-zinc-800 dark:to-zinc-800/40 border border-emerald-100/50 dark:border-zinc-700/50 rounded-xl hover:from-emerald-50 dark:hover:from-zinc-800/80 hover:shadow-md transition-all duration-200">
            <div class="p-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-lg shadow-md shadow-emerald-500/20">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div>
              <h3 class="font-bold text-gray-800 dark:text-zinc-100 text-sm">Registrar Membresía</h3>
              <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Asignar planes, aplicar descuentos vigentes e imprimir comprobantes de pago.</p>
            </div>
          </Link>
        </div>
      </div>

    </div>
  </AppLayout>
</template>