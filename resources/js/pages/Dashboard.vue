
<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';


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
const clientes = ref([])  

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

const diasRestantes = (fechaVencimiento) => {
    if (!fechaVencimiento) return 0;

    const hoy = new Date();
    hoy.setHours(0,0,0,0);

    const vence = new Date(fechaVencimiento);
    vence.setHours(0,0,0,0);

    return Math.ceil((vence - hoy) / (1000 * 60 * 60 * 24));
};

const colorEstado = (cliente) => {

    const fecha = cliente.ultima_membresia?.fecha_vencimiento;

    if (!fecha)
        return 'bg-gray-500';

    const dias = diasRestantes(fecha);

    // Próximo a vencer
    if (dias <= 3)
        return 'bg-red-600';


    if (dias <= 15)
        return 'bg-yellow-500';


    return 'bg-green-600';

};

const formatoFecha = (fecha) => {

    if(!fecha) return '';

    const f = new Date(fecha);

    return `${f.getFullYear()}-${String(f.getMonth()+1).padStart(2,'0')}-${String(f.getDate()).padStart(2,'0')}`;

};

const mostrarAuditoria = ref(false);
const mostrarSeguridad = ref(false);

const toggleAuditoria = () => {
    mostrarAuditoria.value = !mostrarAuditoria.value;
}

const toggleSeguridad = () => {
    mostrarSeguridad.value = !mostrarSeguridad.value;
}

const cargarClientes = async () => {
  try {
    const res = await axios.get('/dashboard/clientes');
    clientes.value = res.data;
  } catch (error) {
    console.log(error);
  }
};

onMounted(() => {
  cargarClientes();
});

const filtroEstado = ref('todos');

const clientesFiltrados = computed(() => {

    const lista = busquedaCliente.value.trim()
        ? resultadosClientes.value
        : clientes.value;


    // Mostrar solamente membresías activas
    return lista.filter(cliente => cliente.es_activa);

});

const esRecepcionista = computed(() => userRole.value === 'Recepcionista');

const tema = computed(() => ({
    banner: esRecepcionista.value
        ? 'bg-gradient-to-r from-sky-600 to-cyan-700 border-sky-500/20'
        : 'bg-gradient-to-r from-orange-600 to-red-700 border-orange-500/20',

    boton: esRecepcionista.value
        ? 'bg-gradient-to-r from-sky-500 to-cyan-600 hover:from-sky-600 hover:to-cyan-700'
        : 'bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700',

    icono: esRecepcionista.value
        ? 'text-sky-500'
        : 'text-orange-500',

    fondo: esRecepcionista.value
        ? 'bg-sky-50 dark:bg-sky-950/20 border-sky-200/50 dark:border-sky-900/30'
        : 'bg-orange-50 dark:bg-orange-950/20 border-orange-200/50 dark:border-orange-900/30'
}));

</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      
      <!-- Banner de bienvenida con gradiente premium energético de Gimnasio -->
      <div
    :class="[
        'relative overflow-hidden text-white p-6 rounded-xl shadow-lg border hover:shadow-xl transition-shadow duration-300 flex justify-between items-center',
        tema.banner
    ]"
>  <div class="relative z-10">
          <h1 class="text-3xl font-extrabold tracking-tight">Panel de Control General</h1>
          <p class="text-orange-100 text-sm mt-2 font-medium">Bienvenido al sistema administrativo del gimnasio. Perfil activo: <span class="bg-white/20 px-2 py-0.5 rounded text-white font-bold text-xs uppercase">{{ userRole }}</span></p>
        </div>
        <div class="relative z-10 hidden sm:block bg-zinc-950 p-1.5 rounded-xl border border-white/10 shadow-md">
          <img src="/logo-gym.png" class="h-16 w-16 object-contain rounded-lg" alt="Eagle Fitness Logo" />
        </div>
        <!-- Elemento de fondo abstracto y moderno -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- Si el usuario es Administrador, muestra los paneles de control financieros y de seguridad -->
      <div v-if="userRole === 'Administrador'" class="space-y-4">
        
        <div class="bg-white dark:bg-zinc-900 rounded-xl shadow border">

         <button
        @click="toggleAuditoria"
        class="w-full flex justify-between items-center p-5 text-left hover:bg-orange-50 dark:hover:bg-zinc-800 rounded-xl transition">

        <div class="flex items-center gap-3">

            <svg xmlns="http://www.w3.org/2000/svg"
                :class="['h-6 w-6', tema.icono]"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10"/>

            </svg>

            <div>

                <h2 class="font-bold text-lg">
                    Auditoría Financiera y Membresías
                </h2>

                <p class="text-xs text-gray-500">
                    Consultar ingresos y estadísticas
                </p>

            </div>

        </div>

        <span class="text-2xl">

            {{ mostrarAuditoria ? '−' : '+' }}

        </span>

      </button>

      <div
        v-show="mostrarAuditoria"
        class="p-5 border-t">

         <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 md:col-span-2 space-y-4 hover:scale-[1.01] hover:shadow-lg transition-all duration-300">
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

      </div>

    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow border">

    <button
        @click="toggleSeguridad"
        class="w-full flex justify-between items-center p-5 text-left hover:bg-orange-50 dark:hover:bg-zinc-800 rounded-xl transition">

        <div class="flex items-center gap-3">

            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-6 w-6 text-orange-500"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 15v2m-6 4h12"/>

            </svg>

            <div>

                <h2 class="font-bold text-lg">

                    Seguridad del Sistema

                </h2>

                <p class="text-xs text-gray-500">

                    Backups y protección de la base de datos

                </p>

            </div>

        </div>

        <span class="text-2xl">

            {{ mostrarSeguridad ? '−' : '+' }}

        </span>

    </button>

    <div
        v-show="mostrarSeguridad"
        class="p-5 border-t">

             <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 flex flex-col justify-between hover:scale-[1.01] hover:shadow-lg transition-all duration-300">
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

        </div>

    </div>


<!-- Si el usuario es Recepcionista -->
<div v-if="userRole === 'Recepcionista'"
  class="bg-white dark:bg-zinc-900 p-5 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 space-y-4 hover:scale-[1.01] hover:shadow-lg transition-all duration-300">

  <h2 class="text-lg font-bold text-gray-800 dark:text-zinc-100 border-b border-gray-100 dark:border-zinc-800 pb-3 flex items-center space-x-2">

    <svg xmlns="http://www.w3.org/2000/svg"
      class="h-5 w-5 text-sky-600"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor">

      <path stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />

    </svg>

    <span>Atención al Cliente y Registro</span>

  </h2>

  <p class="text-sm text-gray-600 dark:text-zinc-400 leading-relaxed">
    Has iniciado sesión como <b>Recepcionista</b>. Desde tu panel de control puedes gestionar clientes, membresías y pagos.
  </p>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">

    <!-- CLIENTES -->
    <Link href="/clientes"
      class="flex items-center space-x-4 p-5 bg-gradient-to-br from-sky-50/70 to-indigo-100/50 dark:from-zinc-800 dark:to-zinc-800/40 border border-sky-200/60 dark:border-zinc-700/50 rounded-xl hover:from-sky-100 hover:to-indigo-200 dark:hover:from-zinc-800/80 hover:shadow-md transition-all duration-200">

      <div class="p-3 bg-gradient-to-r from-sky-600 to-indigo-600 text-white rounded-lg shadow-md shadow-sky-500/20">

        <svg xmlns="http://www.w3.org/2000/svg"
          class="h-6 w-6"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">

          <path stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />

        </svg>

      </div>

      <div>
        <h3 class="font-bold text-gray-800 dark:text-zinc-100 text-sm">
          Gestionar Clientes
        </h3>
        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
          Registrar clientes, actualizar datos o buscar información.
        </p>
      </div>

    </Link>

    <!-- MEMBRESIAS -->
    <Link href="/membresias"
      class="flex items-center space-x-4 p-5 bg-gradient-to-br from-sky-50/70 to-indigo-100/50 dark:from-zinc-800 dark:to-zinc-800/40 border border-sky-200/60 dark:border-zinc-700/50 rounded-xl hover:from-sky-100 hover:to-indigo-200 dark:hover:from-zinc-800/80 hover:shadow-md transition-all duration-200">

      <div class="p-3 bg-gradient-to-r from-sky-600 to-indigo-600 text-white rounded-lg shadow-md shadow-sky-500/20">

        <svg xmlns="http://www.w3.org/2000/svg"
          class="h-6 w-6"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">

          <path stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />

        </svg>

      </div>

      <div>
        <h3 class="font-bold text-gray-800 dark:text-zinc-100 text-sm">
          Registrar Membresía
        </h3>
        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
          Asignar planes, aplicar descuentos e imprimir comprobantes.
        </p>
      </div>

    </Link>

  </div>

</div>

<!-- Panel de Consulta Rápida de Membresías -->
<div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 space-y-4 hover:shadow-lg transition-shadow duration-300">

  <!-- TÍTULO -->
  <h2 class="text-lg font-bold text-gray-800 dark:text-zinc-100 border-b border-gray-100 dark:border-zinc-800 pb-3 flex items-center space-x-2">

    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    </svg>

    <span>Consulta Rápida de Membresías</span>

  </h2>

  <!-- BUSCADOR -->
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
      <span v-else>🔍 Buscar</span>
    </button>

  </div>

  <!-- TABLA -->
  <div class="overflow-x-auto mt-4">

    <table class="min-w-full border border-gray-200 dark:border-zinc-700 rounded-lg overflow-hidden">

      <!-- HEADER -->
      <thead class="bg-orange-600 text-white">
        <tr>
          <th class="px-4 py-3 text-left">CI</th>
          <th class="px-4 py-3 text-left">Cliente</th>
          <th class="px-4 py-3 text-left">Teléfono</th>
          <th class="px-4 py-3 text-left">Membresía</th>
          <th class="px-4 py-3 text-left">Fecha Vencimiento</th>
          <th class="px-4 py-3 text-left"> Estado</th>
        </tr>
      </thead>

      <!-- BODY -->
      <tbody>

        <tr
           v-for="cliente in clientesFiltrados"
          :key="cliente.id_cliente"
          class="border-b hover:bg-orange-50 dark:hover:bg-zinc-800 transition"
        >

          <td class="px-4 py-3">
            {{ cliente.ci }}
          </td>

          <td class="px-4 py-3 font-semibold">
            {{ cliente.nombre }}
          </td>

          <td class="px-4 py-3">
            {{ cliente.telefono}}
          </td>

          <td class="px-4 py-3">
            {{ cliente.ultima_membresia?.nombre_membresia || 'Sin membresía' }}
          </td>
          <td class="px-4 py-3 font-semibold">
          {{ formatoFecha(cliente.ultima_membresia?.fecha_vencimiento) }}
         </td>

          <!-- ESTADO -->
          <td class="px-4 py-3 text-center">

            <div class="flex justify-center items-center gap-3">

              <div
                class="w-10 h-10 rounded-md text-white font-bold flex items-center justify-center"
                :class="colorEstado(cliente)"
              >
                  {{ diasRestantes(cliente.ultima_membresia?.fecha_vencimiento) }}
              </div>

              <span class="font-semibold">
                {{ cliente.es_activa ? 'Activa' : 'Vencida' }}
              </span>

            </div>

          </td>

        </tr>

      </tbody>

    </table>

  </div>

  <!-- SIN RESULTADOS -->
  <div
    v-if="busquedaCliente.trim() && resultadosClientes.length === 0 && !buscando"
    class="text-xs text-gray-500 dark:text-zinc-500 italic pt-2"
  >
    No se encontraron clientes que coincidan con la búsqueda.
  </div>

</div>

    </div>
  </AppLayout>
</template>