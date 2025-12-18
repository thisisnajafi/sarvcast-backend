<template>
  <div class="audio-management">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">مدیریت فایل‌های صوتی</h2>
        <p class="text-gray-600 mt-1">آپلود، پردازش و مدیریت فایل‌های صوتی قسمت‌ها</p>
      </div>
      <div class="flex space-x-3 space-x-reverse">
        <button
          @click="refreshStats"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
          به‌روزرسانی آمار
        </button>
        <button
          @click="showUploadModal = true"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
        >
          + آپلود فایل صوتی
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-blue-100 text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
            </svg>
          </div>
          <div class="mr-4">
            <p class="text-sm font-medium text-gray-600">کل قسمت‌ها</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total_episodes || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-green-100 text-green-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1h2z"></path>
            </svg>
          </div>
          <div class="mr-4">
            <p class="text-sm font-medium text-gray-600">با فایل صوتی</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.episodes_with_audio || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="mr-4">
            <p class="text-sm font-medium text-gray-600">میانگین مدت</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.average_duration || 0 }} ثانیه</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-purple-100 text-purple-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
            </svg>
          </div>
          <div class="mr-4">
            <p class="text-sm font-medium text-gray-600">حجم کل</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total_audio_size_mb || 0 }} مگابایت</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Uploads -->
    <div class="bg-white rounded-lg shadow mb-8">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">آخرین آپلودها</h3>
      </div>
      <div class="p-6">
        <div v-if="recentUploads.length === 0" class="text-center py-8">
          <div class="text-gray-400 mb-4">
            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1h2z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">هیچ فایل صوتی آپلود نشده</h3>
          <p class="text-gray-600">برای شروع، اولین فایل صوتی را آپلود کنید</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">قسمت</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">فرمت</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مدت</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">حجم</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ آپلود</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="upload in recentUploads" :key="upload.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ upload.title }}</div>
                  <div class="text-sm text-gray-500">ID: {{ upload.id }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                    {{ upload.audio_format?.toUpperCase() || 'نامشخص' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatDuration(upload.duration) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ upload.file_size_mb }} مگابایت
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(upload.uploaded_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    @click="viewAudioInfo(upload)"
                    class="text-blue-600 hover:text-blue-900 mr-3"
                  >
                    جزئیات
                  </button>
                  <button
                    @click="deleteAudio(upload)"
                    class="text-red-600 hover:text-red-900"
                  >
                    حذف
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Upload Modal -->
    <div v-if="showUploadModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-medium text-gray-900 mb-4">آپلود فایل صوتی</h3>
        
        <form @submit.prevent="uploadAudio">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">انتخاب قسمت</label>
            <select v-model="uploadForm.episode_id" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              <option value="">انتخاب کنید...</option>
              <option v-for="episode in episodes" :key="episode.id" :value="episode.id">
                {{ episode.title }}
              </option>
            </select>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">فایل صوتی</label>
            <input
              type="file"
              @change="handleFileSelect"
              accept="audio/*"
              required
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">عنوان (اختیاری)</label>
            <input
              v-model="uploadForm.title"
              type="text"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات (اختیاری)</label>
            <textarea
              v-model="uploadForm.description"
              rows="3"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>

          <div class="flex justify-end space-x-3 space-x-reverse">
            <button
              type="button"
              @click="showUploadModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
            >
              انصراف
            </button>
            <button
              type="submit"
              :disabled="uploading"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              {{ uploading ? 'در حال آپلود...' : 'آپلود' }}
            </button>
          </div>
        </form>
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
  name: 'AudioManagement',
  data() {
    return {
      stats: {},
      recentUploads: [],
      episodes: [],
      loading: false,
      uploading: false,
      showUploadModal: false,
      uploadForm: {
        episode_id: '',
        title: '',
        description: '',
        audio_file: null
      },
      message: null
    }
  },
  mounted() {
    this.loadData()
  },
  methods: {
    async loadData() {
      try {
        this.loading = true
        const [statsResponse, episodesResponse] = await Promise.all([
          axios.get('/api/v1/admin/audio-management'),
          axios.get('/api/v1/admin/episodes')
        ])
        
        if (statsResponse.data.success) {
          this.stats = statsResponse.data.data.stats
          this.recentUploads = statsResponse.data.data.recent_uploads
        }
        
        if (episodesResponse.data.success) {
          this.episodes = episodesResponse.data.data.episodes
        }
      } catch (error) {
        this.showMessage('خطا در بارگذاری داده‌ها', 'error')
      } finally {
        this.loading = false
      }
    },

    async refreshStats() {
      await this.loadData()
      this.showMessage('آمار به‌روزرسانی شد', 'success')
    },

    handleFileSelect(event) {
      this.uploadForm.audio_file = event.target.files[0]
    },

    async uploadAudio() {
      try {
        this.uploading = true
        
        const formData = new FormData()
        formData.append('audio_file', this.uploadForm.audio_file)
        formData.append('episode_id', this.uploadForm.episode_id)
        formData.append('title', this.uploadForm.title)
        formData.append('description', this.uploadForm.description)

        const response = await axios.post('/api/v1/admin/audio-management/upload', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        if (response.data.success) {
          this.showMessage('فایل صوتی با موفقیت آپلود شد', 'success')
          this.showUploadModal = false
          this.resetUploadForm()
          await this.loadData()
        }
      } catch (error) {
        this.showMessage('خطا در آپلود فایل صوتی', 'error')
      } finally {
        this.uploading = false
      }
    },

    resetUploadForm() {
      this.uploadForm = {
        episode_id: '',
        title: '',
        description: '',
        audio_file: null
      }
    },

    viewAudioInfo(upload) {
      // Implement audio info view
      this.showMessage('نمایش جزئیات فایل صوتی', 'success')
    },

    async deleteAudio(upload) {
      if (confirm('آیا مطمئن هستید که می‌خواهید این فایل صوتی را حذف کنید؟')) {
        try {
          const response = await axios.delete(`/api/v1/admin/episodes/${upload.id}/audio`)
          
          if (response.data.success) {
            this.showMessage('فایل صوتی حذف شد', 'success')
            await this.loadData()
          }
        } catch (error) {
          this.showMessage('خطا در حذف فایل صوتی', 'error')
        }
      }
    },

    formatDuration(seconds) {
      if (!seconds) return '0:00'
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = seconds % 60
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('fa-IR')
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
.audio-management {
  direction: rtl;
}
</style>
