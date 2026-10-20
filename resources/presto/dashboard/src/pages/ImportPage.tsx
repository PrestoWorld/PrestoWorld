import { createSignal } from 'solid-js';

export default function ImportPage() {
  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Import</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">
          Import content from other systems
        </p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <form class="space-y-4">
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Choose an import system</label>
            <select class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">Select a specific import system:</option>
              <option value="blogger">Blogger</option>
              <option value="categories">Categories and Tags Converter</option>
              <option value="tumblr">Tumblr</option>
              <option value="wordpress">WordPress</option>
              <option value="wordpressxr">WordPress (from a WXR file)</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Choose a file to import</label>
            <input type="file" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            <p class="mt-1 text-xs text-slate-500">
              Choose a WXR file from your computer. The file must be an XML file in the WordPress WXR format.
            </p>
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Fetch external media files during import</label>
            <div class="flex items-start space-x-3">
              <input type="checkbox" id="fetch_media" class="mt-1" />
              <div class="space-y-0.5">
                <label class="text-xs font-bold text-slate-700" for="fetch_media">
                  Download and import file attachments
                </label>
              </div>
            </div>
          </div>
        </form>
        <div class="flex justify-end pt-4">
          <button class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition-colors">
            Upload file and import
          </button>
        </div>
      </div>
    </div>
  );
}