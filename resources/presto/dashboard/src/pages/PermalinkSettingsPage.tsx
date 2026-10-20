import { createSignal } from 'solid-js';

export default function PermalinkSettingsPage() {
  const [permalinkStructure, setPermalinkStructure] = createSignal('post-name');

  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Permalink Settings</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">
          Settings for permalink structure
        </p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <p class="text-xs text-slate-500 leading-relaxed font-semibold max-w-xl">
          These settings control what URLs look like for your posts and pages. Common settings are available below, or you can enter a custom structure in the field below.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-2">
          <label
            class="flex items-start gap-3.5 p-4 border rounded-xl hover:bg-slate-55 relative cursor-pointer overflow-hidden transition-all duration-200"
            classList={{
              'border-indigo-650 bg-indigo-50/10 ring-1 ring-indigo-100': permalinkStructure() === 'plain',
              'border-slate-200 bg-white hover:border-slate-300': permalinkStructure() !== 'plain'
            }}
          >
            <input
              type="radio"
              name="permalink-choice"
              checked={permalinkStructure() === 'plain'}
              onChange={() => setPermalinkStructure('plain')}
              class="mt-1 accent-indigo-600 scale-105 cursor-pointer"
            />
            <div class="space-y-0.5 select-none">
              <span class="text-xs font-bold text-slate-900 block">Plain</span>
              <span class="text-[10px] text-slate-500 font-mono block font-semibold">?p=123</span>
            </div>
          </label>

          <label
            class="flex items-start gap-3.5 p-4 border rounded-xl hover:bg-slate-55 relative cursor-pointer overflow-hidden transition-all duration-200"
            classList={{
              'border-indigo-650 bg-indigo-50/10 ring-1 ring-indigo-100': permalinkStructure() === 'day-and-name',
              'border-slate-200 bg-white hover:border-slate-300': permalinkStructure() !== 'day-and-name'
            }}
          >
            <input
              type="radio"
              name="permalink-choice"
              checked={permalinkStructure() === 'day-and-name'}
              onChange={() => setPermalinkStructure('day-and-name')}
              class="mt-1 accent-indigo-600 scale-105 cursor-pointer"
            />
            <div class="space-y-0.5 select-none">
              <span class="text-xs font-bold text-slate-900 block flex-items-center gap-1.5">
                Day and name
                <span class="text-[9px] font-bold px-1 rounded bg-indigo-100 text-indigo-700">RECOMMENDED</span>
              </span>
              <span class="text-[10px] text-indigo-650 font-mono block font-semibold">/2024/10/20/sample-post/</span>
            </div>
          </label>

          <label
            class="flex items-start gap-3.5 p-4 border rounded-xl hover:bg-slate-55 relative cursor-pointer overflow-hidden transition-all duration-200"
            classList={{
              'border-indigo-650 bg-indigo-50/10 ring-1 ring-indigo-100': permalinkStructure() === 'month-and-name',
              'border-slate-200 bg-white hover:border-slate-300': permalinkStructure() !== 'month-and-name'
            }}
          >
            <input
              type="radio"
              name="permalink-choice"
              checked={permalinkStructure() === 'month-and-name'}
              onChange={() => setPermalinkStructure('month-and-name')}
              class="mt-1 accent-indigo-600 scale-105 cursor-pointer"
            />
            <div class="space-y-0.5 select-none">
              <span class="text-xs font-bold text-slate-900 block flex-items-center gap-1.5">
                Month and name
                <span class="text-[9px] font-bold px-1 rounded bg-indigo-100 text-indigo-700">RECOMMENDED</span>
              </span>
              <span class="text-[10px] text-indigo-650 font-mono block font-semibold">/2024/10/sample-post/</span>
            </div>
          </label>

          <label
            class="flex items-start gap-3.5 p-4 border rounded-xl hover:bg-slate-55 relative cursor-pointer overflow-hidden transition-all duration-200"
            classList={{
              'border-indigo-650 bg-indigo-50/10 ring-1 ring-indigo-100': permalinkStructure() === 'numeric',
              'border-slate-200 bg-white hover:border-slate-300': permalinkStructure() !== 'numeric'
            }}
          >
            <input
              type="radio"
              name="permalink-choice"
              checked={permalinkStructure() === 'numeric'}
              onChange={() => setPermalinkStructure('numeric')}
              class="mt-1 accent-indigo-600 scale-105 cursor-pointer"
            />
            <div class="space-y-0.5 select-none">
              <span class="text-xs font-bold text-slate-900 block">Numeric</span>
              <span class="text-[10px] text-indigo-650 font-mono block font-semibold">/123/</span>
            </div>
          </label>

          <label
            class="flex items-start gap-3.5 p-4 border rounded-xl hover:bg-slate-55 relative cursor-pointer overflow-hidden transition-all duration-200"
            classList={{
              'border-indigo-650 bg-indigo-50/10 ring-1 ring-indigo-100': permalinkStructure() === 'post-name',
              'border-slate-200 bg-white hover:border-slate-300': permalinkStructure() !== 'post-name'
            }}
          >
            <input
              type="radio"
              name="permalink-choice"
              checked={permalinkStructure() === 'post-name'}
              onChange={() => setPermalinkStructure('post-name')}
              class="mt-1 accent-indigo-600 scale-105 cursor-pointer"
            />
            <div class="space-y-0.5 select-none">
              <span class="text-xs font-bold text-slate-900 block flex-items-center gap-1.5">
                Post name
                <span class="text-[9px] font-bold px-1 rounded bg-indigo-100 text-indigo-700">RECOMMENDED</span>
              </span>
              <span class="text-[10px] text-indigo-650 font-mono block font-semibold">/sample-post/</span>
            </div>
          </label>
        </div>

        <div class="space-y-4 pt-4 border-t border-slate-100">
          <label class="block text-xs font-bold text-slate-700 mb-1" for="custom_structure">
            Custom Structure
          </label>
          <input
            id="custom_structure"
            type="text"
            placeholder="/%year%/%monthnum%/%day%/%postname%/"
            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <div class="flex items-center gap-2 mt-2">
            <button type="button" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
              <strong>Available tags</strong>
            </button>
            <div class="text-xs text-slate-500">
              %year% %monthnum% %day% %hour% %minute% %second% %post_id% %postname% %category% %author%%
            </div>
          </div>
        </div>

        <div class="space-y-4 pt-4 border-t border-slate-100">
          <div class="flex items-start space-x-3">
            <input type="checkbox" id="category_base" class="mt-1" />
            <div class="space-y-0.5">
              <label class="text-xs font-bold text-slate-700" for="category_base">
                Category base
              </label>
              <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="category" />
            </div>
          </div>
        </div>

        <div class="space-y-4 pt-4 border-t border-slate-100">
          <div class="flex items-start space-x-3">
            <input type="checkbox" id="tag_base" class="mt-1" />
            <div class="space-y-0.5">
              <label class="text-xs font-bold text-slate-700" for="tag_base">
                Tag base
              </label>
              <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="tag" />
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition-colors">
            Save Changes
          </button>
        </div>
      </div>
    </div>
  );
}