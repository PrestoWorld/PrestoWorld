import { createSignal } from 'solid-js';

export default function ReadingSettingsPage() {
  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Reading Settings</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">
          Settings for the front page and feeds
        </p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <form class="space-y-4">
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Your homepage displays</label>
            <div class="space-y-1">
              <label class="flex items-start space-x-3">
                <input type="radio" name="front_page" value="posts" class="mt-1" />
                <div class="space-y-0.5">
                  <p class="text-sm font-medium text-slate-900">Your latest posts</p>
                  <p class="text-xs text-slate-500">Show the most recent posts on your front page.</p>
                </div>
              </label>
              <label class="flex items-start space-x-3">
                <input type="radio" name="front_page" value="page" class="mt-1" />
                <div class="space-y-0.5">
                  <p class="text-sm font-medium text-slate-900">A static page (select below)</p>
                  <p class="text-xs text-slate-500">Use a page as your front page.</p>
                </div>
              </label>
            </div>
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Posts page</label>
            <select class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">Select</option>
              <option value="1">Blog</option>
              <option value="2">News</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Blog pages show at most</label>
            <input type="number" min="1" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            <p class="mt-1 text-xs text-slate-500">posts</p>
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Syndication feeds show the most recent</label>
            <input type="number" min="1" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            <p class="mt-1 text-xs text-slate-500">items</p>
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">For each article in a feed, show</label>
            <div class="space-y-1">
              <label class="flex items-start space-x-3">
                <input type="radio" name="feed_type" value="full" class="mt-1" />
                <span>Full text</span>
              </label>
              <label class="flex items-start space-x-3">
                <input type="radio" name="feed_type" value="excerpt" class="mt-1" />
                <span>Excerpt</span>
              </label>
            </div>
          </div>
        </form>
        <div class="flex justify-end pt-4">
          <button class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition-colors">
            Save Changes
          </button>
        </div>
      </div>
    </div>
  );
}