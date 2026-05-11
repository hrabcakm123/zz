<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface UsageDetail {
  timestamp: string
  token: string
  city: string
  country: string
}

interface AnimationStats {
  total_unique_uses: number
  details: UsageDetail[]
}

const pendulumStats = ref<AnimationStats | null>(null)
const ballBeamStats = ref<AnimationStats | null>(null)

const isLoading = ref(true)
const error = ref<string | null>(null)

const fetchStats = async () => {
  isLoading.value = true
  error.value = null
  try {
    const token = import.meta.env.VITE_API_TOKEN || 'tajnykluc123'
    const response = await fetch('/zz/api/stats/animations', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })

    if (!response.ok) throw new Error(`Chyba: ${response.status}`)

    const data = await response.json()
    pendulumStats.value = data.inverted_pendulum || null
    ballBeamStats.value = data.ball_beam || null
  } catch (err: any) {
    console.error(err)
    error.value = err.message || 'Nepodarilo sa načítať štatistiky.'
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchStats)

const formatDate = (iso: string) => {
  const d = new Date(iso)
  return d.toLocaleString()
}
</script>

<template>
  <div class="max-w-5xl mx-auto">
    <h2 class="text-3xl font-extrabold text-gray-800 mb-6">{{ t('stats.title') }}</h2>

    <!-- loading / error -->
    <div v-if="isLoading" class="text-center py-10 text-gray-500">Načítavam štatistiky...</div>
    <div v-else-if="error" class="text-center py-10 text-red-600">{{ error }}</div>

    <template v-else>
      <!-- Pendulum -->
      <div class="mb-10">
        <h3 class="text-2xl font-bold text-gray-700 mb-3">{{ t('stats.pendulum') }}</h3>
        <p class="text-lg mb-4">
          {{ t('stats.totalUnique') }}: <span class="font-semibold">{{ pendulumStats?.total_unique_uses ?? 0 }}</span>
        </p>
        <div v-if="pendulumStats?.details?.length" class="overflow-x-auto">
          <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-2 text-left">{{ t('stats.timestamp') }}</th>
                <th class="px-4 py-2 text-left">{{ t('stats.token') }}</th>
                <th class="px-4 py-2 text-left">{{ t('stats.city') }}</th>
                <th class="px-4 py-2 text-left">{{ t('stats.country') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, idx) in pendulumStats.details"
                :key="idx"
                class="border-t border-gray-100 hover:bg-gray-50"
              >
                <td class="px-4 py-2 text-sm">{{ formatDate(item.timestamp) }}</td>
                <td class="px-4 py-2 text-sm font-mono">{{ item.token.slice(0, 8) }}…</td>
                <td class="px-4 py-2 text-sm">{{ item.city }}</td>
                <td class="px-4 py-2 text-sm">{{ item.country }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="text-gray-500 italic">{{ t('stats.noData') }}</p>
      </div>

      <!-- Ball Beam -->
      <div>
        <h3 class="text-2xl font-bold text-gray-700 mb-3">{{ t('stats.ballBeam') }}</h3>
        <p class="text-lg mb-4">
          {{ t('stats.totalUnique') }}: <span class="font-semibold">{{ ballBeamStats?.total_unique_uses ?? 0 }}</span>
        </p>
        <div v-if="ballBeamStats?.details?.length" class="overflow-x-auto">
          <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-2 text-left">{{ t('stats.timestamp') }}</th>
                <th class="px-4 py-2 text-left">{{ t('stats.token') }}</th>
                <th class="px-4 py-2 text-left">{{ t('stats.city') }}</th>
                <th class="px-4 py-2 text-left">{{ t('stats.country') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, idx) in ballBeamStats.details"
                :key="idx"
                class="border-t border-gray-100 hover:bg-gray-50"
              >
                <td class="px-4 py-2 text-sm">{{ formatDate(item.timestamp) }}</td>
                <td class="px-4 py-2 text-sm font-mono">{{ item.token.slice(0, 8) }}…</td>
                <td class="px-4 py-2 text-sm">{{ item.city }}</td>
                <td class="px-4 py-2 text-sm">{{ item.country }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="text-gray-500 italic">{{ t('stats.noData') }}</p>
      </div>
    </template>
  </div>
</template>