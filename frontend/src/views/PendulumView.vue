<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Line } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

const targetPosition = ref(0.2)
const isAnimating = ref(false)

// ---- CANVAS LOGIKA PRE ANIMÁCIU ----
const canvasRef = ref<HTMLCanvasElement | null>(null)
const currentCartX = ref(0) // Aktuálna pozícia vozíka
const currentAngle = ref(0) // Aktuálny uhol kyvadla

// Funkcia na vykreslenie jedného snímku (frame)
const drawPendulum = (cartX: number, angle: number) => {
  const canvas = canvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  const width = canvas.width
  const height = canvas.height

  // Vyčistenie plátna pred každým prekreslením
  ctx.clearRect(0, 0, width, height)

  // Konštanty pre kreslenie
  const scale = 200 // Priblíženie (pixely na meter)
  const cartWidth = 60
  const cartHeight = 30
  const pendulumLength = 100
  const trackY = height - 50 // Výška koľajnice

  // Prepočet pozície vozíka z metrov do pixelov (stred plátna je pozícia 0)
  const pixelX = (width / 2) + (cartX * scale)

  // 1. Vykreslenie koľajnice
  ctx.beginPath()
  ctx.moveTo(0, trackY + cartHeight / 2)
  ctx.lineTo(width, trackY + cartHeight / 2)
  ctx.strokeStyle = '#9ca3af' // Sivá farba
  ctx.lineWidth = 2
  ctx.stroke()

  // 2. Vykreslenie vozíka
  ctx.fillStyle = '#3b82f6' // Modrá farba
  ctx.fillRect(pixelX - cartWidth / 2, trackY - cartHeight / 2, cartWidth, cartHeight)

  // 3. Vykreslenie tyče kyvadla
  const tipX = pixelX + pendulumLength * Math.sin(angle)
  const tipY = trackY - pendulumLength * Math.cos(angle)

  ctx.beginPath()
  ctx.moveTo(pixelX, trackY) // Začiatok v strede vozíka
  ctx.lineTo(tipX, tipY) // Koniec tyče
  ctx.strokeStyle = '#f97316' // Oranžová farba
  ctx.lineWidth = 6
  ctx.lineCap = 'round'
  ctx.stroke()

  // Kĺb (bodka v strede vozíka)
  ctx.beginPath()
  ctx.arc(pixelX, trackY, 6, 0, 2 * Math.PI)
  ctx.fillStyle = '#1e3a8a'
  ctx.fill()
}

// Keď sa komponent načíta, nakreslíme počiatočný stav
onMounted(() => {
  drawPendulum(currentCartX.value, currentAngle.value)
})

// Ak sa manuálne zmení currentCartX alebo currentAngle, hneď to prekreslíme
watch([currentCartX, currentAngle], () => {
  drawPendulum(currentCartX.value, currentAngle.value)
})

// ---- GRAF A SIMULÁCIA ----
const chartData = ref({
  labels: ['0', '1', '2', '3', '4', '5'],
  datasets: [
    { label: 'Pozícia (m)', backgroundColor: '#3b82f6', borderColor: '#3b82f6', data: [0, 0.05, 0.12, 0.18, 0.2, 0.2] },
    { label: 'Uhol (rad)', backgroundColor: '#f97316', borderColor: '#f97316', data: [0, -0.1, -0.05, 0.02, 0, 0] }
  ]
})
const chartOptions = ref({ responsive: true, maintainAspectRatio: false })

const startSimulation = () => {
  isAnimating.value = true

  const times = [0, 1, 2, 3, 4, 5]
  const positions = (chartData.value.datasets[0]?.data || []) as number[]
  const angles = (chartData.value.datasets[1]?.data || []) as number[]

  let startTime: number | null = null

  const animate = (timestamp: number) => {
    if (!startTime) startTime = timestamp
    const elapsedSeconds = (timestamp - startTime) / 1000

    // Zabezpečenie proti 'undefined'
    const maxTime = times[times.length - 1] || 5

    if (elapsedSeconds >= maxTime) {
      currentCartX.value = positions[positions.length - 1] || 0
      currentAngle.value = angles[angles.length - 1] || 0
      isAnimating.value = false
      return
    }

    let i = 0
    // Poistka, aby sme nevyšli mimo dĺžky poľa
    while (i < times.length - 1 && (times[i + 1] || 0) < elapsedSeconds) {
      i++
    }

    // Vytiahnutie hodnôt s fallbackom (|| 0) pre TypeScript
    const t0 = times[i] || 0
    const t1 = times[i + 1] || 1
    const progress = (elapsedSeconds - t0) / (t1 - t0)

    const pos0 = positions[i] || 0
    const pos1 = positions[i + 1] || 0
    const ang0 = angles[i] || 0
    const ang1 = angles[i + 1] || 0

    currentCartX.value = pos0 + (pos1 - pos0) * progress
    currentAngle.value = ang0 + (ang1 - ang0) * progress

    requestAnimationFrame(animate)
  }

  requestAnimationFrame(animate)
}
</script>

<template>
  <div class="max-w-6xl mx-auto">
    <h2 class="text-3xl font-extrabold text-gray-800 mb-6">Prevrátené kyvadlo</h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 h-fit">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Parametre simulácie</h3>
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-600 mb-2">Cieľová pozícia (r)</label>
          <input v-model="targetPosition" type="number" step="0.1"
            class="w-full p-2.5 border border-gray-300 rounded-lg outline-none">
        </div>
        <button @click="startSimulation" :disabled="isAnimating"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg disabled:bg-gray-400">
          {{ isAnimating ? 'Animujem...' : 'Spustiť simuláciu' }}
        </button>
      </div>

      <div class="lg:col-span-2 flex flex-col gap-6">

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-700 mb-4">Vizuálna animácia</h3>
          <div class="w-full flex justify-center bg-gray-50 rounded-lg border border-gray-100 overflow-hidden">
            <canvas ref="canvasRef" width="600" height="250" class="max-w-full"></canvas>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-700 mb-4">Priebeh veličín</h3>
          <div class="h-64 relative w-full">
            <Line :data="chartData" :options="chartOptions" />
          </div>
        </div>

      </div>
    </div>
  </div>
</template>