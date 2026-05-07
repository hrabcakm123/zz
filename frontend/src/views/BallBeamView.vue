<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import { useI18n } from 'vue-i18n'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

const { t } = useI18n()

// parametre formulára
const initialR = ref(0.0)
const initialAlpha = ref(0.0)
const reference1 = ref(0.25)
const reference2 = ref(0.5)
const duration = ref(5)
const dt = ref(0.01)
const L = ref(1.0)

const isLoading = ref(false)
const isAnimating = ref(false)

interface Frame {
  time: string
  r: string
  alpha: string
  ball_x: string
  ball_y: string
}

const frames = ref<Frame[]>([])

// canvas
const canvasRef = ref<HTMLCanvasElement | null>(null)
const currentBallPos = ref(0)
const currentAngle = ref(0)

const fixedParams = {
  m: 0.111,
  R: 0.015,
  J: 9.99e-6,
  g: 9.8
}

// graf – reaktívne dátové polia
const chartLabels = ref<number[]>([])
const chartPositions = ref<number[]>([])
const chartAngles = ref<number[]>([])

// graf s computed, aby sa label menil podľa jazyka
const chartData = computed(() => ({
  labels: chartLabels.value,
  datasets: [
    {
      label: t('ballbeam.positionLabel'),
      borderColor: '#10b981',
      backgroundColor: '#10b981',
      data: chartPositions.value
    },
    {
      label: t('ballbeam.angleLabel'),
      borderColor: '#8b5cf6',
      backgroundColor: '#8b5cf6',
      data: chartAngles.value
    }
  ]
}))

const chartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  animation: false,
  scales: {
    x: { type: 'linear', min: 0, max: 10 },
    y: { min: -0.5, max: 1.0 }
  }
})

// kreslenie guličky na tyči
const drawBallBeam = (ballPos: number, angle: number) => {
  const canvas = canvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  const width = canvas.width
  const height = canvas.height
  ctx.clearRect(0, 0, width, height)

  const scale = 400
  const beamLength = L.value * scale
  const ballRadius = 15

  ctx.save()
  ctx.translate(width / 2, height / 2)
  ctx.rotate(angle)

  // tyč
  ctx.beginPath()
  ctx.moveTo(-beamLength / 2, 0)
  ctx.lineTo(beamLength / 2, 0)
  ctx.strokeStyle = '#6b7280'
  ctx.lineWidth = 6
  ctx.lineCap = 'round'
  ctx.stroke()

  // gulička
  const pixelX = ballPos * scale
  ctx.beginPath()
  ctx.arc(pixelX, -ballRadius, ballRadius, 0, 2 * Math.PI)
  ctx.fillStyle = '#10b981'
  ctx.fill()
  ctx.lineWidth = 2
  ctx.strokeStyle = '#047857'
  ctx.stroke()

  // podpera
  ctx.beginPath()
  ctx.moveTo(-10, 0)
  ctx.lineTo(10, 0)
  ctx.lineTo(0, 20)
  ctx.fillStyle = '#374151'
  ctx.fill()

  ctx.restore()
}

onMounted(() => drawBallBeam(currentBallPos.value, currentAngle.value))
watch([currentBallPos, currentAngle], () => drawBallBeam(currentBallPos.value, currentAngle.value))

// animácia
const runAnimation = (allFrames: Frame[]) => {
  const allTimes = allFrames.map(f => parseFloat(f.time))
  const allPositions = allFrames.map(f => parseFloat(f.r))
  const allAngles = allFrames.map(f => parseFloat(f.alpha))

  // nastav osi grafu
  const timeMin = allTimes[0]
  const timeMax = allTimes[allTimes.length - 1]
  const posMin = Math.min(...allPositions)
  const posMax = Math.max(...allPositions)
  const angMin = Math.min(...allAngles)
  const angMax = Math.max(...allAngles)
  const yMin = Math.min(posMin, angMin) - 0.1
  const yMax = Math.max(posMax, angMax) + 0.1

  chartOptions.value = {
    ...chartOptions.value,
    scales: {
      x: { type: 'linear', min: timeMin, max: timeMax },
      y: { min: yMin, max: yMax }
    }
  }

  // vyprázdni graf
  chartLabels.value = []
  chartPositions.value = []
  chartAngles.value = []

  const startTime = performance.now()
  const totalDuration = parseFloat(allFrames[allFrames.length - 1].time)

  const animate = (now: number) => {
    const elapsed = (now - startTime) / 1000

    if (elapsed >= totalDuration) {
      chartLabels.value = allTimes
      chartPositions.value = allPositions
      chartAngles.value = allAngles
      currentBallPos.value = allPositions[allPositions.length - 1]
      currentAngle.value = allAngles[allAngles.length - 1]
      isAnimating.value = false
      return
    }

    // interpolácia
    let index = 0
    while (index < allFrames.length - 1 && parseFloat(allFrames[index + 1].time) < elapsed) index++

    const f0 = allFrames[index]
    const f1 = allFrames[Math.min(index + 1, allFrames.length - 1)]
    const t0 = parseFloat(f0.time)
    const t1 = parseFloat(f1.time)
    const progress = (elapsed - t0) / (t1 - t0 || 1)

    currentBallPos.value = parseFloat(f0.r) + (parseFloat(f1.r) - parseFloat(f0.r)) * progress
    currentAngle.value = parseFloat(f0.alpha) + (parseFloat(f1.alpha) - parseFloat(f0.alpha)) * progress

    // zobraz body po index
    const visibleCount = index + 1
    chartLabels.value = allTimes.slice(0, visibleCount)
    chartPositions.value = allPositions.slice(0, visibleCount)
    chartAngles.value = allAngles.slice(0, visibleCount)

    requestAnimationFrame(animate)
  }

  requestAnimationFrame(animate)
}

// API volanie
const startSimulation = async () => {
  isLoading.value = true
  isAnimating.value = false

  try {
    const token = import.meta.env.VITE_API_TOKEN || 'tajnykluc123'

    const response = await fetch('http://localhost:8000/api/animation/ballbeam', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        m: fixedParams.m,
        R: fixedParams.R,
        J: fixedParams.J,
        L: L.value,
        g: fixedParams.g,
        r0: initialR.value,
        rdot0: 0,
        alpha0: initialAlpha.value,
        alphadot0: 0,
        ref1: reference1.value,
        ref2: reference2.value,
        T: duration.value,
        dt: dt.value
      })
    })

    if (!response.ok) throw new Error(`Chyba API: ${response.status}`)

    const data = await response.json()
    frames.value = data.frames
    isAnimating.value = true
    runAnimation(frames.value)

  } catch (error) {
    console.error(error)
    alert(t('ballbeam.error'))
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="max-w-6xl mx-auto">
    <h2 class="text-3xl font-extrabold text-gray-800 mb-6">{{ t('ballbeam.title') }}</h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- panel parametrov -->
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 h-fit">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">{{ t('ballbeam.params') }}</h3>

        <div class="grid grid-cols-2 gap-3 mb-5">
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('ballbeam.initR') }}</label>
            <input v-model.number="initialR" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('ballbeam.initAlpha') }}</label>
            <input v-model.number="initialAlpha" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-5">
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('ballbeam.ref1') }}</label>
            <input v-model.number="reference1" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('ballbeam.ref2') }}</label>
            <input v-model.number="reference2" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-5">
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('ballbeam.duration') }}</label>
            <input v-model.number="duration" type="number" step="1" class="w-full p-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('ballbeam.dt') }}</label>
            <input v-model.number="dt" type="number" step="0.001" class="w-full p-2 border rounded-lg">
          </div>
        </div>

        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-600">{{ t('ballbeam.beamLength') }}</label>
          <input v-model.number="L" type="number" step="0.1" class="w-full p-2 border rounded-lg">
        </div>

        <button @click="startSimulation" :disabled="isAnimating || isLoading"
          class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg disabled:bg-gray-400 disabled:cursor-not-allowed">
          {{ isLoading ? t('ballbeam.computing') : isAnimating ? t('ballbeam.animating') : t('ballbeam.start') }}
        </button>
      </div>

      <!-- animácia a graf -->
      <div class="lg:col-span-2 flex flex-col gap-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-700 mb-4">{{ t('ballbeam.animation') }}</h3>
          <div class="w-full flex justify-center bg-gray-50 rounded-lg border border-gray-100 overflow-hidden">
            <canvas ref="canvasRef" width="600" height="250" class="max-w-full"></canvas>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-700 mb-4">{{ t('ballbeam.chart') }}</h3>
          <div class="h-64 relative w-full">
            <Line :data="chartData" :options="chartOptions" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>