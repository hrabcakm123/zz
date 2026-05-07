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

const initAngle = ref(0.0)
const initPosition = ref(0.0)
const ref1 = ref(0.2)
const ref2 = ref(0.5)
const T = ref(10)
const dt = ref(0.05)

const isLoading = ref(false)
const isAnimating = ref(false)

interface Frame {
  time: string
  x: string
  theta: string
  pendulum_x: string
  pendulum_y: string
}

const frames = ref<Frame[]>([])

const canvasRef = ref<HTMLCanvasElement | null>(null)
const currentCartX = ref(0)
const currentAngle = ref(0)

const fixedParams = {
  M: 0.5,
  m: 0.2,
  b: 0.1,
  I: 0.006,
  g: 9.8,
  l: 0.3
}

const drawPendulum = (cartX: number, angle: number) => {
  const canvas = canvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  const width = canvas.width
  const height = canvas.height
  ctx.clearRect(0, 0, width, height)

  const scale = 200
  const cartWidth = 60
  const cartHeight = 30
  const pendulumLength = fixedParams.l * scale
  const trackY = height - 50
  const pixelX = (width / 2) + (cartX * scale)

  ctx.beginPath()
  ctx.moveTo(0, trackY + cartHeight / 2)
  ctx.lineTo(width, trackY + cartHeight / 2)
  ctx.strokeStyle = '#9ca3af'
  ctx.lineWidth = 2
  ctx.stroke()

  ctx.fillStyle = '#3b82f6'
  ctx.fillRect(pixelX - cartWidth / 2, trackY - cartHeight / 2, cartWidth, cartHeight)

  const tipX = pixelX + pendulumLength * Math.sin(angle)
  const tipY = trackY - pendulumLength * Math.cos(angle)

  ctx.beginPath()
  ctx.moveTo(pixelX, trackY)
  ctx.lineTo(tipX, tipY)
  ctx.strokeStyle = '#f97316'
  ctx.lineWidth = 6
  ctx.lineCap = 'round'
  ctx.stroke()

  ctx.beginPath()
  ctx.arc(pixelX, trackY, 6, 0, 2 * Math.PI)
  ctx.fillStyle = '#1e3a8a'
  ctx.fill()
}

onMounted(() => drawPendulum(currentCartX.value, currentAngle.value))
watch([currentCartX, currentAngle], () => drawPendulum(currentCartX.value, currentAngle.value))

const chartLabels = ref<number[]>([])
const chartPositions = ref<number[]>([])
const chartAngles = ref<number[]>([])

const chartData = computed(() => ({
  labels: chartLabels.value,
  datasets: [
    {
      label: t('pendulum.positionLabel'),
      borderColor: '#3b82f6',
      backgroundColor: '#3b82f6',
      data: chartPositions.value
    },
    {
      label: t('pendulum.angleLabel'),
      borderColor: '#f97316',
      backgroundColor: '#f97316',
      data: chartAngles.value
    }
  ]
}))

const chartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  animation: false,
  scales: {
    x: { type: 'linear', min: 0, max: 20 },
    y: { min: -0.5, max: 1.0 }
  }
})

const runAnimation = (allFrames: Frame[]) => {
  const allTimes = allFrames.map(f => parseFloat(f.time))
  const allPositions = allFrames.map(f => parseFloat(f.x))
  const allAngles = allFrames.map(f => parseFloat(f.theta))

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
      currentCartX.value = allPositions[allPositions.length - 1]
      currentAngle.value = allAngles[allAngles.length - 1]
      isAnimating.value = false
      return
    }

    let index = 0
    while (index < allFrames.length - 1 && parseFloat(allFrames[index + 1].time) < elapsed) index++

    const f0 = allFrames[index]
    const f1 = allFrames[Math.min(index + 1, allFrames.length - 1)]
    const t0 = parseFloat(f0.time)
    const t1 = parseFloat(f1.time)
    const progress = (elapsed - t0) / (t1 - t0 || 1)

    currentCartX.value = parseFloat(f0.x) + (parseFloat(f1.x) - parseFloat(f0.x)) * progress
    currentAngle.value = parseFloat(f0.theta) + (parseFloat(f1.theta) - parseFloat(f0.theta)) * progress

    const visibleCount = index + 1
    chartLabels.value = allTimes.slice(0, visibleCount)
    chartPositions.value = allPositions.slice(0, visibleCount)
    chartAngles.value = allAngles.slice(0, visibleCount)

    requestAnimationFrame(animate)
  }

  requestAnimationFrame(animate)
}

const startSimulation = async () => {
  isLoading.value = true
  isAnimating.value = false

  try {
    const token = import.meta.env.VITE_API_TOKEN || 'tajnykluc123'

    const response = await fetch('http://localhost:8000/api/animation/pendulum', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        M: fixedParams.M,
        m: fixedParams.m,
        b: fixedParams.b,
        I: fixedParams.I,
        g: fixedParams.g,
        l: fixedParams.l,
        initialAngle: initAngle.value,
        initialPosition: initPosition.value,
        reference1: ref1.value,
        reference2: ref2.value,
        duration: T.value,
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
    alert(t('pendulum.error'))
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="max-w-6xl mx-auto">
    <h2 class="text-3xl font-extrabold text-gray-800 mb-6">{{ t('pendulum.title') }}</h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 h-fit">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">{{ t('pendulum.params') }}</h3>

        <div class="grid grid-cols-2 gap-3 mb-5">
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('pendulum.initAngle') }}</label>
            <input v-model.number="initAngle" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('pendulum.initPosition') }}</label>
            <input v-model.number="initPosition" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-5">
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('pendulum.ref1') }}</label>
            <input v-model.number="ref1" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('pendulum.ref2') }}</label>
            <input v-model.number="ref2" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-6">
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('pendulum.duration') }}</label>
            <input v-model.number="T" type="number" step="1" class="w-full p-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">{{ t('pendulum.dt') }}</label>
            <input v-model.number="dt" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
        </div>

        <button @click="startSimulation" :disabled="isAnimating || isLoading"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg disabled:bg-gray-400 disabled:cursor-not-allowed">
          {{ isLoading ? t('pendulum.computing') : isAnimating ? t('pendulum.animating') : t('pendulum.start') }}
        </button>
      </div>

      <div class="lg:col-span-2 flex flex-col gap-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-700 mb-4">{{ t('pendulum.animation') }}</h3>
          <div class="w-full flex justify-center bg-gray-50 rounded-lg border border-gray-100 overflow-hidden">
            <canvas ref="canvasRef" width="600" height="250" class="max-w-full"></canvas>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-700 mb-4">{{ t('pendulum.chart') }}</h3>
          <div class="h-64 relative w-full">
            <Line :data="chartData" :options="chartOptions" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>