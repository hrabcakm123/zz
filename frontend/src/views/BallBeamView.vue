<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Line } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

const targetPosition = ref(0.25)
const isAnimating = ref(false)

// ---- CANVAS LOGIKA PRE ANIMÁCIU ----
const canvasRef = ref<HTMLCanvasElement | null>(null)
const currentBallPos = ref(0) // Aktuálna pozícia guličky od stredu v metroch
const currentAngle = ref(0) // Aktuálny uhol naklonenia tyče

const drawBallBeam = (ballPos: number, angle: number) => {
  const canvas = canvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  const width = canvas.width
  const height = canvas.height

  // Vyčistenie plátna
  ctx.clearRect(0, 0, width, height)

  // Konštanty pre kreslenie
  const scale = 400 // Priblíženie (pixely na meter) - gulička sa hýbe v menších číslach
  const beamLength = 400
  const ballRadius = 15

  // Uloženie základného stavu plátna
  ctx.save()

  // Posunieme bod [0,0] presne do stredu plátna
  ctx.translate(width / 2, height / 2)
  
  // Otočíme celé plátno o požadovaný uhol (v radiánoch)
  ctx.rotate(angle)

  // 1. Vykreslenie tyče (kreslíme ju vodorovne, keďže plátno je už otočené)
  ctx.beginPath()
  ctx.moveTo(-beamLength / 2, 0)
  ctx.lineTo(beamLength / 2, 0)
  ctx.strokeStyle = '#6b7280' // Tailwind gray-500
  ctx.lineWidth = 6
  ctx.lineCap = 'round'
  ctx.stroke()

  // 2. Vykreslenie guličky na tyči
  const pixelX = ballPos * scale
  ctx.beginPath()
  // Gulička leží na tyči, takže jej Y pozícia je mínus jej polomer
  ctx.arc(pixelX, -ballRadius, ballRadius, 0, 2 * Math.PI)
  ctx.fillStyle = '#10b981' // Tailwind emerald-500
  ctx.fill()
  ctx.lineWidth = 2
  ctx.strokeStyle = '#047857' // Tmavší okraj guličky
  ctx.stroke()

  // 3. Stredový kĺb (podpera)
  ctx.beginPath()
  ctx.moveTo(-10, 0)
  ctx.lineTo(10, 0)
  ctx.lineTo(0, 20)
  ctx.fillStyle = '#374151' // Tmavosivá
  ctx.fill()

  // Vrátenie plátna do pôvodného neotočeného stavu
  ctx.restore()
}

onMounted(() => drawBallBeam(currentBallPos.value, currentAngle.value))

watch([currentBallPos, currentAngle], () => {
  drawBallBeam(currentBallPos.value, currentAngle.value)
})

// ---- GRAF A SIMULÁCIA ----
const chartData = ref({
  labels: ['0', '1', '2', '3', '4', '5'],
  datasets: [
    { label: 'Pozícia guličky (m)', backgroundColor: '#10b981', borderColor: '#10b981', data: [0, 0.1, 0.18, 0.23, 0.25, 0.25] },
    { label: 'Uhol tyče (rad)', backgroundColor: '#8b5cf6', borderColor: '#8b5cf6', data: [0, 0.05, 0.02, -0.01, 0, 0] }
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
    
    const maxTime = times[times.length - 1] || 5
    
    if (elapsedSeconds >= maxTime) {
      currentBallPos.value = positions[positions.length - 1] || 0
      currentAngle.value = angles[angles.length - 1] || 0
      isAnimating.value = false
      return
    }
    
    let i = 0
    while (i < times.length - 1 && (times[i + 1] || 0) < elapsedSeconds) {
      i++
    }
    
    const t0 = times[i] || 0
    const t1 = times[i + 1] || 1
    const progress = (elapsedSeconds - t0) / (t1 - t0) 
    
    const pos0 = positions[i] || 0
    const pos1 = positions[i + 1] || 0
    const ang0 = angles[i] || 0
    const ang1 = angles[i + 1] || 0

    currentBallPos.value = pos0 + (pos1 - pos0) * progress
    currentAngle.value = ang0 + (ang1 - ang0) * progress
    
    requestAnimationFrame(animate)
  }
  
  requestAnimationFrame(animate)
}
</script>

<template>
  <div class="max-w-6xl mx-auto">
    <h2 class="text-3xl font-extrabold text-gray-800 mb-6">Gulička na tyči</h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 h-fit">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Parametre simulácie</h3>
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-600 mb-2">Cieľová pozícia guličky (r)</label>
          <input v-model="targetPosition" type="number" step="0.05" class="w-full p-2.5 border border-gray-300 rounded-lg outline-none">
        </div>
        <button @click="startSimulation" :disabled="isAnimating" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg disabled:bg-gray-400">
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