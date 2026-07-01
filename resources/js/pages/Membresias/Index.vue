<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 bg-white dark:bg-zinc-900 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 max-w-4xl mx-auto m-4 hover:shadow-lg transition-shadow duration-300">
      <h1 class="text-2xl font-extrabold tracking-tight text-gray-800 dark:text-zinc-100 mb-6 pb-4 border-b border-gray-100 dark:border-zinc-800/80 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        <span>Registrar Membresía y Pago (CU-04 / CU-05)</span>
      </h1>

      <form @submit.prevent="enviarFormulario" class="space-y-6 text-gray-900 dark:text-zinc-100">
      
      <div class="bg-zinc-50 dark:bg-zinc-800/30 p-5 rounded-xl border border-zinc-200/50 dark:border-zinc-800/60">
        <h2 class="text-xs font-black uppercase text-orange-600 dark:text-orange-500 tracking-widest mb-4">1. Seleccionar Cliente</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Buscar Cliente (CI o Apellido)</label>
            <input 
              v-model="filtroBusqueda" 
              @input="filtrarClientes"
              type="text" 
              placeholder="Escriba para buscar..."
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Cliente Seleccionado *</label>
            <select 
              v-model="form.id_cliente" 
              required
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
            >
              <option value="" disabled>-- Seleccione un cliente de la lista --</option>
              <option 
                v-for="cliente in clientesFiltrados" 
                :key="cliente.id_cliente" 
                :value="cliente.id_cliente"
              >
                {{ cliente.persona.ci }} - {{ cliente.persona.nombre }} {{ cliente.persona.apellido }}
              </option>
            </select>
            <p v-if="clientesFiltrados.length === 0" class="text-xs text-red-500 dark:text-red-400 mt-1.5 font-medium">
              Si no existe, el CU-04 permite registrarlo primero en el módulo de Clientes.
            </p>
          </div>
        </div>

        <!-- Alerta y Checkbox de Pago Adelantado si el cliente seleccionado ya cuenta con membresía activa -->
        <div v-if="clienteTieneMembresiaActiva" class="mt-4 p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 rounded-xl space-y-3">
          <p class="text-xs text-amber-800 dark:text-amber-300 font-semibold flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>El cliente seleccionado ya cuenta con una membresía activa que vence el <strong class="underline">{{ clienteFechaLimite }}</strong>.</span>
          </p>
          <div class="flex items-center gap-2">
            <input 
              type="checkbox" 
              id="pago_adelantado" 
              v-model="form.pago_adelantado"
              class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500 bg-white dark:bg-zinc-800 dark:border-zinc-700 cursor-pointer"
            />
            <label for="pago_adelantado" class="text-xs font-bold text-gray-700 dark:text-zinc-300 cursor-pointer select-none">
              Registrar como Pago Adelantado (La nueva membresía iniciará el <strong class="text-orange-650 dark:text-orange-400 font-black">{{ fechaInicioAdelantada }}</strong>)
            </label>
          </div>
          <p v-if="!form.pago_adelantado" class="text-xs text-red-500 dark:text-red-400 font-bold">
            ⚠ Debe marcar la casilla de pago adelantado para poder continuar con la renovación.
          </p>
        </div>
      </div>

      <div class="bg-zinc-50 dark:bg-zinc-800/30 p-5 rounded-xl border border-zinc-200/50 dark:border-zinc-800/60">
        <h2 class="text-xs font-black uppercase text-orange-600 dark:text-orange-500 tracking-widest mb-4">2. Configuración del Plan</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Tipo de Membresía *</label>
            <select 
              v-model="form.id_tipo_membresia" 
              @change="actualizarMontoYFechas"
              required
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
            >
              <option value="" disabled>-- Seleccione un Plan --</option>
              <option 
                v-for="tipo in tiposMembresia" 
                :key="tipo.id_tipo_membresia" 
                :value="tipo.id_tipo_membresia"
              >
                {{ tipo.nombre_membresia }} ({{ tipo.duracion_dias }} días)
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Meses a Pagar *</label>
            <select 
              v-model.number="form.cantidad_periodos" 
              @change="actualizarMontoYFechas"
              required
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
            >
              <option v-for="n in 12" :key="n" :value="n">
                x{{ n }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Fecha de Inicio</label>
            <input 
              v-model="form.fecha_inicio" 
              @change="actualizarMontoYFechas"
              type="date" 
              required
              :disabled="clienteTieneMembresiaActiva && form.pago_adelantado"
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200 disabled:bg-zinc-100 dark:disabled:bg-zinc-800/80 disabled:cursor-not-allowed disabled:text-gray-500 dark:disabled:text-zinc-400"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Fecha de Vencimiento</label>
            <input 
              v-model="fechaVencimientoCalculada" 
              type="date" 
              readonly
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-zinc-100 dark:bg-zinc-800/50 text-orange-600 dark:text-orange-400 font-bold focus:outline-none cursor-not-allowed opacity-90"
            />
          </div>
        </div>

        <!-- Selector de Promociones Vigentes -->
        <div class="mt-4 pt-4 border-t border-zinc-200/55 dark:border-zinc-800/60">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Aplicar Promoción Vigente</label>
              <select 
                v-model="form.id_promocion" 
                @change="actualizarMontoYFechas"
                class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
              >
                <option value="">-- Sin Promoción --</option>
                <option 
                  v-for="promocion in promociones" 
                  :key="promocion.id_promocion" 
                  :value="promocion.id_promocion"
                >
                  {{ promocion.nombre_promocion }} ({{ promocion.porcentaje_descuento }}% de descuento)
                </option>
              </select>
            </div>
            <div v-if="promocionSeleccionada" class="flex flex-col justify-center p-3.5 bg-orange-50/50 dark:bg-orange-950/10 border border-orange-200/50 dark:border-orange-900/20 rounded-lg text-xs font-medium">
              <span class="text-orange-850 dark:text-orange-300 font-black block">🎉 Promoción Activa: {{ promocionSeleccionada.nombre_promocion }}</span>
              <span class="text-gray-500 dark:text-zinc-400 block mt-1">{{ promocionSeleccionada.descripcion || 'Sin descripción adicional.' }}</span>
              <span class="text-emerald-600 dark:text-emerald-400 font-extrabold block mt-1">Descuento del {{ promocionSeleccionada.porcentaje_descuento }}% aplicado al total.</span>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-zinc-50 dark:bg-zinc-800/30 p-5 rounded-xl border border-zinc-200/50 dark:border-zinc-800/60">
        <h2 class="text-xs font-black uppercase text-orange-600 dark:text-orange-500 tracking-widest mb-4">3. Procesar Transacción Económica</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start mb-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Monto Total Base (Bs.)</label>
            <input 
              v-model="montoBase" 
              type="text" 
              readonly 
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-zinc-100 dark:bg-zinc-800/50 text-gray-500 dark:text-zinc-400 font-extrabold focus:outline-none cursor-not-allowed opacity-90"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Monto Descontado (Bs.)</label>
            <input 
              v-model="montoDescontado" 
              type="text" 
              readonly 
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm font-extrabold focus:outline-none cursor-not-allowed opacity-90"
              :class="form.id_promocion && parseFloat(montoDescontado) > 0
                ? 'bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-450 border-rose-300 dark:border-rose-900/50' 
                : 'bg-zinc-100 dark:bg-zinc-800/50 text-gray-400 dark:text-zinc-500 border-gray-200 dark:border-zinc-800'"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Monto Final a Cobrar (Bs.)</label>
            <input 
              v-model="montoFinal" 
              type="text" 
              readonly 
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm font-black focus:outline-none cursor-not-allowed opacity-90"
              :class="form.id_promocion && parseFloat(montoDescontado) > 0
                ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-450 border-emerald-300 dark:border-emerald-900/50' 
                : 'bg-zinc-100 dark:bg-zinc-800/50 text-gray-900 dark:text-zinc-100 border-gray-200 dark:border-zinc-800'"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Forma de Pago *</label>
            <select 
              v-model="form.metodo_pago" 
              required
              class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
            >
              <option value="Efectivo">Efectivo</option>
              <option value="Transferencia">Transferencia / Código QR</option>
            </select>
          </div>
        </div>

        <!-- Campos adicionales para transferencia -->
        <div v-if="form.metodo_pago === 'Transferencia'" class="border-t border-zinc-200/55 dark:border-zinc-800/60 pt-4 mt-4 space-y-4">
          <h3 class="text-xs font-black uppercase text-orange-600 dark:text-orange-500 tracking-widest">Detalle de la Transferencia Bancaria</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Banco Origen *</label>
              <input 
                v-model="form.banco_origen" 
                type="text" 
                placeholder="Ej: Banco Mercantil"
                required
                class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Banco Destino *</label>
              <input 
                v-model="form.banco_destino" 
                type="text" 
                placeholder="Ej: Banco Unión (Gym)"
                required
                class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Cuenta Destino *</label>
              <input 
                v-model="form.cuenta_destino" 
                type="text" 
                placeholder="Ej: 10000045612"
                required
                class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Código de Transacción *</label>
              <input 
                v-model="form.codigo_transaccion" 
                type="text" 
                placeholder="Ej: TR-9823412"
                required
                class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"
              />
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Adjuntar Comprobante QR *</label>
            <input 
              type="file" 
              @change="procesarArchivoComprobante"
              accept="image/png, image/jpeg"
              required
              class="block w-full text-xs text-gray-500 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-50 dark:file:bg-orange-950/20 file:text-orange-700 dark:file:text-orange-400 hover:file:bg-orange-100 dark:hover:file:bg-orange-950/40 transition-colors file:cursor-pointer cursor-pointer"
            />
            <p class="text-[10px] text-gray-400 dark:text-zinc-500 mt-1">Formatos admitidos: JPG, PNG de forma obligatoria. Máximo 2MB.</p>
            <span v-if="errorImagen" class="text-xs text-red-500 dark:text-red-400 font-semibold block mt-1">{{ errorImagen }}</span>
          </div>
        </div>
      </div>

      <!-- Lista de errores de validación de Inertia -->
      <div v-if="Object.keys(form.errors).length > 0" class="text-sm text-red-650 dark:text-red-400 font-semibold bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-900/30 p-4 rounded-lg">
        <ul class="list-disc pl-4 space-y-1">
          <li v-for="(error, index) in form.errors" :key="index">{{ error }}</li>
        </ul>
      </div>

      <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800/80">
        <button 
          type="button" 
          @click="resetearFormulario" 
          class="px-5 py-2.5 border border-gray-200 dark:border-zinc-800 rounded-lg text-gray-650 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm font-semibold transition-all duration-200"
        >
          Limpiar Campos
        </button>
        <button 
          type="submit" 
          :disabled="form.processing || !!errorImagen || (clienteTieneMembresiaActiva && !form.pago_adelantado)"
          class="px-6 py-2.5 bg-orange-600 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 disabled:opacity-50"
        >
          {{ form.processing ? 'Registrando...' : 'Confirmar Inscripción y Pago' }}
        </button>
      </div>

      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Registrar Membresía', href: '/membresias' },
];

// Recibimos las colecciones cargadas desde el controlador de Laravel
const props = defineProps({
  clientes: Array,
  tiposMembresia: Array,
  promociones: Array
});

// Variables reactivas locales para el buscador y cálculo de fechas
const filtroBusqueda = ref('');
const clientesFiltrados = ref([...props.clientes]);
const montoBase = ref('0.00');
const montoDescontado = ref('0.00');
const montoFinal = ref('0.00');
const fechaVencimientoCalculada = ref('');
const errorImagen = ref('');

// Variables reactivas para el control de membresía existente y pago adelantado
const clienteTieneMembresiaActiva = ref(false);
const clienteFechaLimite = ref('');
const fechaInicioAdelantada = ref('');

// Obtener la fecha de hoy en formato local YYYY-MM-DD
const fechaHoy = new Date().toISOString().split('T')[0];

// Inicialización de la estructura de datos mediante useForm de Inertia
const form = useForm({
  id_cliente: '',
  id_tipo_membresia: '',
  id_promocion: '',
  fecha_inicio: fechaHoy,
  metodo_pago: 'Efectivo',
  comprobante_foto: null, // Aquí viaja el archivo binario del QR
  banco_origen: '',
  banco_destino: '',
  cuenta_destino: '',
  codigo_transaccion: '',
  pago_adelantado: false,
  cantidad_periodos: 1
});

// Obtener reactivamente la promoción seleccionada
const promocionSeleccionada = computed(() => {
  if (!form.id_promocion) {
    return null;
  }
  return props.promociones.find(p => p.id_promocion === form.id_promocion) || null;
});

// Monitorear la selección del cliente para detectar si ya tiene una membresía activa
watch(() => form.id_cliente, (nuevoId) => {
  form.pago_adelantado = false;
  clienteTieneMembresiaActiva.value = false;
  clienteFechaLimite.value = '';
  fechaInicioAdelantada.value = '';

  if (nuevoId) {
    const cliente = props.clientes.find(c => c.id_cliente === nuevoId);
    if (cliente && cliente.fecha_limite) {
      const hoyStr = new Date().toISOString().split('T')[0];
      // Solo consideramos membresía activa si tiene al menos una membresía registrada y el límite está vigente
      if (cliente.membresias_count > 0 && cliente.fecha_limite >= hoyStr) {
        clienteTieneMembresiaActiva.value = true;
        clienteFechaLimite.value = cliente.fecha_limite;
        
        // Calcular fecha de inicio adelantada = fecha_limite + 1 día
        const fecha = new Date(cliente.fecha_limite + 'T00:00:00');
        fecha.setDate(fecha.getDate() + 1);
        fechaInicioAdelantada.value = fecha.toISOString().split('T')[0];
      }
    }
  }
  actualizarMontoYFechas();
});

// Monitorear si se marca el checkbox de pago adelantado
watch(() => form.pago_adelantado, (val) => {
  if (val) {
    form.fecha_inicio = fechaInicioAdelantada.value;
  } else {
    form.fecha_inicio = fechaHoy;
  }
  actualizarMontoYFechas();
});

// Filtrar la lista de clientes reactivamente por CI o Apellido (CU-03)
const filtrarClientes = () => {
  const busqueda = filtroBusqueda.value.toLowerCase().trim();
  if (!busqueda) {
    clientesFiltrados.value = [...props.clientes];
  } else {
    clientesFiltrados.value = props.clientes.filter(cliente => {
      const ci = cliente.persona.ci.toLowerCase();
      const apellido = cliente.persona.apellido.toLowerCase();
      return ci.includes(busqueda) || apellido.includes(busqueda);
    });
  }
};

// Calcula automáticamente el vencimiento y el monto según el catálogo (CU-04 / CU-08)
const actualizarMontoYFechas = () => {
  const planSeleccionado = props.tiposMembresia.find(
    t => t.id_tipo_membresia === form.id_tipo_membresia
  );

  if (planSeleccionado && form.fecha_inicio) {
    const cant = form.cantidad_periodos || 1;
    const base = planSeleccionado.precio * cant;
    montoBase.value = parseFloat(base).toFixed(2);
    
    // Calcular descuento de promoción si existe
    let final = base;
    let descuento = 0;
    if (form.id_promocion) {
      const promo = props.promociones.find(p => p.id_promocion === form.id_promocion);
      if (promo) {
        descuento = (base * promo.porcentaje_descuento) / 100;
        final = base - descuento;
      }
    }
    montoFinal.value = parseFloat(final).toFixed(2);
    montoDescontado.value = parseFloat(descuento).toFixed(2);

    // Calcular fecha de vencimiento sumando la duración en días multiplicada por la cantidad de períodos
    const fecha = new Date(form.fecha_inicio + 'T00:00:00');
    fecha.setDate(fecha.getDate() + (parseInt(planSeleccionado.duracion_dias) * cant));
    fechaVencimientoCalculada.value = fecha.toISOString().split('T')[0];
  } else {
    montoBase.value = '0.00';
    montoFinal.value = '0.00';
    fechaVencimientoCalculada.value = '';
  }
};

// Validación e inspección del archivo cargado (Cumple Flujo Alternativo A3 de CU-05)
const procesarArchivoComprobante = (event) => {
  const archivo = event.target.files[0];
  errorImagen.value = '';

  if (!archivo) {
    form.comprobante_foto = null;
    return;
  }

  // Validar extensión de tipo imagen obligatoria
  const tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg'];
  if (!tiposPermitidos.includes(archivo.type)) {
    errorImagen.value = 'Error: El comprobante debe ser una imagen válida (JPG o PNG).';
    form.comprobante_foto = null;
    return;
  }

  // Validar tamaño máximo (Ej: 2MB)
  if (archivo.size > 2 * 1024 * 1024) {
    errorImagen.value = 'Error: El archivo supera el tamaño máximo permitido (2MB).';
    form.comprobante_foto = null;
    return;
  }

  // Si pasa los filtros, se almacena en el formulario
  form.comprobante_foto = archivo;
};

// Envío unificado de datos a la ruta POST de Laravel
const enviarFormulario = () => {
  form.post('/membresias', {
    preserveScroll: true,
    onSuccess: () => {
      alert('Membresía y Pago registrados exitosamente en el sistema.');
      resetearFormulario();
    }
  });
};

const resetearFormulario = () => {
  form.reset();
  form.fecha_inicio = fechaHoy;
  filtroBusqueda.value = '';
  clientesFiltrados.value = [...props.clientes];
  montoBase.value = '0.00';
  montoDescontado.value = '0.00';
  montoFinal.value = '0.00';
  fechaVencimientoCalculada.value = '';
  errorImagen.value = '';
  clienteTieneMembresiaActiva.value = false;
  clienteFechaLimite.value = '';
  fechaInicioAdelantada.value = '';
};
</script>