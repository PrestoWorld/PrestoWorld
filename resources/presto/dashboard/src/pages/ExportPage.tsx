import { createSignal } from 'solid-js';

export default function ExportPage() {
  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Export</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">
          Export your content as an XML file
        </p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <form class="space-y-4">
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Choose what to export</label>
            <div class="space-y-1">
              <label class="flex items-start space-x-3">
                <input type="radio" name="export_type" value="all" class="mt-1" checked />
                <div class="space-y-0.5">
                  <p class="text-sm font-medium text-slate-900">All content</p>
                  <p class="text-xs text-slate-500">Includes posts, pages, comments, custom fields, terms, navigation menus and custom posts</p>
                </div>
              </label>
              <label class="flex items-start space-x-3">
                <input type="radio" name="export_type" value="posts" class="mt-1" />
                <div class="space-y-0.5">
                  <p class="text-sm font-medium text-slate-900">Posts</p>
                  <p class="text-xs text-slate-500"></p>
                </div>
              </label>
              <label class="flex items-start space-x-3">
                <input type="radio" name="export_type" value="pages" class="mt-1" />
                <div class="space-y-0.5">
                  <p class="text-sm font-medium text-slate-900">Pages</p>
                  <p class="text-xs text-slate-500"></p>
                </div>
              </label>
              <label class="flex items-start space-x-3">
                <input type="radio" name="export_type" value="comments" class="mt-1" />
                <div class="space-y-0.5">
                  <p class="text-sm font-medium text-slate-900">Comments</p>
                  <p class="text-xs text-slate-500"></p>
                </div>
              </label>
              <label class="flex items-start space-x-3">
                <input type="radio" name="export_type" value="categories" class="mt-1" />
                <div class="space-y-0.5">
                  <p class="text-sm font-medium text-slate-900">Categories</p>
                  <p class="text-xs text-slate-500"></p>
                </div>
              </label>
              <label class="flex items-start space-x-3">
                <input type="radio" name="export_type" value="tags" class="mt-1" />
                <div class="space-y-0.5">
                  <p class="text-sm font-medium text-slate-900">Tags</p>
                  <p class="text-xs text-slate-500"></p>
                </div>
              </label>
              <label class="flex items-start space-x-3">
                <input type="radio" name="export_type" value="custom-fields" class="mt-1" />
                <div class="space-y-0.5">
                  <p class="text-sm font-medium text-slate-900">Custom fields</p>
                  <p class="text-xs text-slate-500"></p>
                </div>
              </label>
            </div>
          </div>
        </form>
        <div class="flex justify-end pt-4">
          <button class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition-colors">
            Download Export File
          </button>
        </div>
      </div>
    </div>
  );
}