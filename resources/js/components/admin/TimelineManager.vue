<template>
  <div class="timeline-manager">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">مدیریت تایم‌لاین تصاویر</h2>
        <p class="text-gray-600 mt-1">مدیریت تصاویر نمایشی برای قسمت: {{ episode.title }}</p>
      </div>
      <div class="flex space-x-3 space-x-reverse">
        <button
          @click="validateTimeline"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
          اعتبارسنجی
        </button>
        <button
          @click="optimizeTimeline"
          :disabled="loading"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
        >
          بهینه‌سازی
        </button>
        <button
          @click="saveTimeline"
          :disabled="loading"
          class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50"
        >
          ذخیره تایم‌لاین
        </button>
      </div>
    </div>

    <!-- Episode Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">عنوان قسمت</label>
          <p class="mt-1 text-sm text-gray-900">{{ episode.title }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">مدت زمان (ثانیه)</label>
          <p class="mt-1 text-sm text-gray-900">{{ episode.duration }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">وضعیت تایم‌لاین</label>
          <span
            :class="episode.use_image_timeline ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
          >
            {{ episode.use_image_timeline ? 'فعال' : 'غیرفعال' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Timeline Editor -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">ویرایشگر تایم‌لاین</h3>
        <p class="text-sm text-gray-600 mt-1">تصاویر و زمان‌بندی آنها را تنظیم کنید</p>
      </div>

      <div class="p-6">
        <!-- Add New Entry -->
        <div class="mb-6">
          <button
            @click="addTimelineEntry"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            + افزودن تصویر جدید
          </button>
        </div>

        <!-- Timeline Entries -->
        <div class="space-y-4">
          <div
            v-for="(entry, index) in timelineEntries"
            :key="index"
            class="border border-gray-200 rounded-lg p-4"
          >
            <div class="flex justify-between items-start mb-4">
              <h4 class="text-sm font-medium text-gray-900">تصویر {{ index + 1 }}</h4>
              <button
                @click="removeTimelineEntry(index)"
                class="text-red-600 hover:text-red-800"
              >
                حذف
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Start Time -->
              <div>
                <label class="block text-sm font-medium text-gray-700">زمان شروع (ثانیه)</label>
                <input
                  v-model.number="entry.start_time"
                  type="number"
                  min="0"
                  :max="episode.duration"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="0"
                />
              </div>

              <!-- End Time -->
              <div>
                <label class="block text-sm font-medium text-gray-700">زمان پایان (ثانیه)</label>
                <input
                  v-model.number="entry.end_time"
                  type="number"
                  min="1"
                  :max="episode.duration"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="10"
                />
              </div>

              <!-- Image URL -->
              <div>
                <label class="block text-sm font-medium text-gray-700">آدرس تصویر</label>
                <input
                  v-model="entry.image_url"
                  type="url"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="https://example.com/image.jpg"
                />
              </div>
            </div>

            <!-- Image Preview -->
            <div v-if="entry.image_url" class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">پیش‌نمایش تصویر</label>
              <img
                :src="entry.image_url"
                :alt="`تصویر ${index + 1}`"
                class="w-32 h-20 object-cover rounded border"
                @error="handleImageError"
              />
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="timelineEntries.length === 0" class="text-center py-12">
          <div class="text-gray-400 mb-4">
            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">هیچ تصویری اضافه نشده</h3>
          <p class="text-gray-600 mb-4">برای شروع، اولین تصویر را اضافه کنید</p>
          <button
            @click="addTimelineEntry"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            افزودن تصویر
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics -->
    <div v-if="statistics" class="mt-6 bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">آمار تایم‌لاین</h3>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ statistics.total_entries }}</div>
          <div class="text-sm text-gray-600">کل ورودی‌ها</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ statistics.unique_images }}</div>
          <div class="text-sm text-gray-600">تصاویر منحصر به فرد</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-600">{{ statistics.first_image_start || 0 }}</div>
          <div class="text-sm text-gray-600">شروع (ثانیه)</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-orange-600">{{ statistics.last_image_end || 0 }}</div>
          <div class="text-sm text-gray-600">پایان (ثانیه)</div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-600">در حال پردازش...</p>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <div v-if="message" class="fixed top-4 right-4 z-50">
      <div
        :class="message.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="text-white px-6 py-3 rounded-lg shadow-lg"
      >
        {{ message.text }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TimelineManager',
  props: {
    episode: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      timelineEntries: [],
      statistics: null,
      loading: false,
      message: null
    }
  },
  mounted() {
    this.loadTimeline()
  },
  methods: {
    async loadTimeline() {
      try {
        this.loading = true
        const response = await axios.get(`/api/v1/admin/episodes/${this.episode.id}/timeline`)
        
        if (response.data.success) {
          this.timelineEntries = response.data.data.timeline.map(entry => ({
            start_time: entry.start_time,
            end_time: entry.end_time,
            image_url: entry.image_url
          }))
          this.statistics = response.data.data.statistics
        }
      } catch (error) {
        this.showMessage('خطا در بارگذاری تایم‌لاین', 'error')
      } finally {
        this.loading = false
      }
    },

    addTimelineEntry() {
      this.timelineEntries.push({
        start_time: 0,
        end_time: 10,
        image_url: ''
      })
    },

    removeTimelineEntry(index) {
      this.timelineEntries.splice(index, 1)
    },

    async saveTimeline() {
      try {
        this.loading = true
        
        const timelineData = this.timelineEntries.map((entry, index) => ({
          start_time: entry.start_time,
          end_time: entry.end_time,
          image_url: entry.image_url,
          image_order: index + 1
        }))

        const response = await axios.post(`/api/v1/admin/episodes/${this.episode.id}/timeline`, {
          image_timeline: timelineData
        })

        if (response.data.success) {
          this.showMessage('تایم‌لاین با موفقیت ذخیره شد', 'success')
          await this.loadTimeline()
        }
      } catch (error) {
        this.showMessage('خطا در ذخیره تایم‌لاین', 'error')
      } finally {
        this.loading = false
      }
    },

    async validateTimeline() {
      try {
        this.loading = true
        
        const timelineData = this.timelineEntries.map(entry => ({
          start_time: entry.start_time,
          end_time: entry.end_time,
          image_url: entry.image_url
        }))

        const response = await axios.post('/api/v1/admin/timeline/validate', {
          episode_duration: this.episode.duration,
          image_timeline: timelineData
        })

        if (response.data.success) {
          this.showMessage('تایم‌لاین معتبر است', 'success')
        }
      } catch (error) {
        const errors = error.response?.data?.errors || {}
        const errorMessage = Object.values(errors).flat().join(', ')
        this.showMessage(`خطا در اعتبارسنجی: ${errorMessage}`, 'error')
      } finally {
        this.loading = false
      }
    },

    async optimizeTimeline() {
      try {
        this.loading = true
        
        const timelineData = this.timelineEntries.map(entry => ({
          start_time: entry.start_time,
          end_time: entry.end_time,
          image_url: entry.image_url
        }))

        const response = await axios.post('/api/v1/admin/timeline/optimize', {
          image_timeline: timelineData
        })

        if (response.data.success) {
          const optimized = response.data.data.optimized_timeline
          this.timelineEntries = optimized.map(entry => ({
            start_time: entry.start_time,
            end_time: entry.end_time,
            image_url: entry.image_url
          }))
          this.showMessage(`تایم‌لاین بهینه شد (${response.data.data.original_count} → ${response.data.data.optimized_count})`, 'success')
        }
      } catch (error) {
        this.showMessage('خطا در بهینه‌سازی تایم‌لاین', 'error')
      } finally {
        this.loading = false
      }
    },

    handleImageError(event) {
      event.target.src = '/images/placeholder-image.png'
    },

    showMessage(text, type) {
      this.message = { text, type }
      setTimeout(() => {
        this.message = null
      }, 5000)
    }
  }
}
</script>

<style scoped>
.timeline-manager {
  direction: rtl;
}
</style>
