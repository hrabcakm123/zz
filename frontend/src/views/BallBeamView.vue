<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
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

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

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

const canvasRef = ref<HTMLCanvasElement | null>(null)
const currentBallPos = ref(0)
const currentAngle = ref(0)

const fixedParams = {
  m: 0.111,
  R: 0.015,
  J: 9.99e-6,
  g: 9.8
}

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

  ctx.beginPath()
  ctx.moveTo(-beamLength / 2, 0)
  ctx.lineTo(beamLength / 2, 0)
  ctx.strokeStyle = '#6b7280'
  ctx.lineWidth = 6
  ctx.lineCap = 'round'
  ctx.stroke()

  const pixelX = ballPos * scale
  ctx.beginPath()
  ctx.arc(pixelX, -ballRadius, ballRadius, 0, 2 * Math.PI)
  ctx.fillStyle = '#10b981'
  ctx.fill()
  ctx.lineWidth = 2
  ctx.strokeStyle = '#047857'
  ctx.stroke()

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

const chartData = ref({
  labels: [] as number[],
  datasets: [
    {
      label: 'Pozícia guličky (m)',
      borderColor: '#10b981',
      backgroundColor: '#10b981',
      data: [] as number[]
    },
    {
      label: 'Uhol tyče (rad)',
      borderColor: '#8b5cf6',
      backgroundColor: '#8b5cf6',
      data: [] as number[]
    }
  ]
})

const chartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  animation: false,
  scales: {
    x: { type: 'linear', min: 0, max: 10 },
    y: { min: -0.5, max: 1.0 }
  }
})

const runAnimation = (allFrames: Frame[]) => {
  const allTimes = allFrames.map(f => parseFloat(f.time))
  const allPositions = allFrames.map(f => parseFloat(f.r))
  const allAngles = allFrames.map(f => parseFloat(f.alpha))

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

  chartData.value = {
    labels: [],
    datasets: [
      { label: 'Pozícia guličky (m)', borderColor: '#10b981', backgroundColor: '#10b981', data: [] },
      { label: 'Uhol tyče (rad)', borderColor: '#8b5cf6', backgroundColor: '#8b5cf6', data: [] }
    ]
  }

  const startTime = performance.now()
  const totalDuration = parseFloat(allFrames[allFrames.length - 1].time)

  const animate = (now: number) => {
    const elapsed = (now - startTime) / 1000

    if (elapsed >= totalDuration) {
      chartData.value = {
        labels: allTimes,
        datasets: [
          { label: 'Pozícia guličky (m)', borderColor: '#10b981', backgroundColor: '#10b981', data: allPositions },
          { label: 'Uhol tyče (rad)', borderColor: '#8b5cf6', backgroundColor: '#8b5cf6', data: allAngles }
        ]
      }
      currentBallPos.value = allPositions[allPositions.length - 1]
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

    currentBallPos.value = parseFloat(f0.r) + (parseFloat(f1.r) - parseFloat(f0.r)) * progress
    currentAngle.value = parseFloat(f0.alpha) + (parseFloat(f1.alpha) - parseFloat(f0.alpha)) * progress

    const visibleCount = index + 1
    chartData.value = {
      labels: allTimes.slice(0, visibleCount),
      datasets: [
        {
          label: 'Pozícia guličky (m)',
          borderColor: '#10b981',
          backgroundColor: '#10b981',
          data: allPositions.slice(0, visibleCount)
        },
        {
          label: 'Uhol tyče (rad)',
          borderColor: '#8b5cf6',
          backgroundColor: '#8b5cf6',
          data: allAngles.slice(0, visibleCount)
        }
      ]
    }

    requestAnimationFrame(animate)
  }

  requestAnimationFrame(animate)
}

const startSimulation = async () => {
  isLoading.value = true
  isAnimating.value = false

  try {
    const response = await fetch('http://localhost:8000/api/animation/ballbeam', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer tajnykluc123'
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
    alert('Nepodarilo sa spustiť simuláciu.')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="max-w-6xl mx-auto">
    <h2 class="text-3xl font-extrabold text-gray-800 mb-6">Gulička na tyči</h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 h-fit">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Parametre simulácie</h3>

        <div class="grid grid-cols-2 gap-3 mb-5">
          <div>
            <label class="block text-sm font-medium text-gray-600">Init poloha (m)</label>
            <input v-model.number="initialR" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Init uhol tyče (rad)</label>
            <input v-model.number="initialAlpha" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-5">
          <div>
            <label class="block text-sm font-medium text-gray-600">Cieľová poloha 1 (m)</label>
            <input v-model.number="reference1" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Cieľová poloha 2 (m)</label>
            <input v-model.number="reference2" type="number" step="0.01" class="w-full p-2 border rounded-lg">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-5">
          <div>
            <label class="block text-sm font-medium text-gray-600">Dĺžka fázy (s)</label>
            <input v-model.number="duration" type="number" step="1" class="w-full p-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">dt (s)</label>
            <input v-model.number="dt" type="number" step="0.001" class="w-full p-2 border rounded-lg">
          </div>
        </div>

        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-600">Dĺžka tyče (m) – vizualizácia</label>
          <input v-model.number="L" type="number" step="0.1" class="w-full p-2 border rounded-lg">
        </div>

        <button @click="startSimulation" :disabled="isAnimating || isLoading"
          class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg disabled:bg-gray-400 disabled:cursor-not-allowed">
          {{ isLoading ? 'Počítam...' : isAnimating ? 'Animácia beží...' : 'Spustiť simuláciu' }}
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