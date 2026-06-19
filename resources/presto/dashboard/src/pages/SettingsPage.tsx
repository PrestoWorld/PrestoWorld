interface SettingsPageProps {
  siteTitle: () => string;
  setSiteTitle: (v: string) => void;
  siteTagline: () => string;
  setSiteTagline: (v: string) => void;
  siteUrl: () => string;
  setSiteUrl: (v: string) => void;
  adminEmail: () => string;
  setAdminEmail: (v: string) => void;
  membershipOpen: () => boolean;
  setMembershipOpen: (v: boolean) => void;
  defaultRole: () => string;
  setDefaultRole: (v: string) => void;
  permalinkStructure: () => string;
  setPermalinkStructure: (v: string) => void;
  saveSettings: (e: Event) => void;
}

export default function SettingsPage(props: SettingsPageProps) {
  return (
    <form onSubmit={props.saveSettings} class="space-y-6 max-w-4xl">

      <div class="wp-card">
        <div class="card-header border-b border-slate-100 bg-slate-50/20 px-6 py-4.5">
          <h3 class="font-extrabold text-sm tracking-tight text-slate-800">General Settings</h3>
        </div>
        <div class="card-body p-6 divide-y divide-slate-100">

          <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5 first:pt-0">
            <label for="site-title" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
              Site Title
            </label>
            <div class="md:col-span-3">
              <input
                id="site-title"
                type="text"
                value={props.siteTitle()}
                onInput={(e) => props.setSiteTitle(e.currentTarget.value)}
                class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-600 hover:bg-slate-50 focus:bg-white rounded-xl px-4 py-3 outline-none transition-all"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5">
            <label for="site-tagline" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
              Tagline
            </label>
            <div class="md:col-span-3">
              <input
                id="site-tagline"
                type="text"
                value={props.siteTagline()}
                onInput={(e) => props.setSiteTagline(e.currentTarget.value)}
                class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-600 hover:bg-slate-50 focus:bg-white rounded-xl px-4 py-3 outline-none transition-all"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5">
            <label for="site-email" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
              Admin Email
            </label>
            <div class="md:col-span-3">
              <input
                id="site-email"
                type="email"
                value={props.adminEmail()}
                onInput={(e) => props.setAdminEmail(e.currentTarget.value)}
                class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-600 hover:bg-slate-50 focus:bg-white rounded-xl px-4 py-3 outline-none transition-all"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5 last:pb-0">
            <label for="site-url" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
              Site URL
            </label>
            <div class="md:col-span-3">
              <input
                id="site-url"
                type="text"
                value={props.siteUrl()}
                onInput={(e) => props.setSiteUrl(e.currentTarget.value)}
                class="w-full text-xs font-bold bg-[#f8fafc] border border-slate-200 focus:border-indigo-600 hover:bg-slate-50 focus:bg-white rounded-xl px-4 py-3 outline-none transition-all font-mono text-indigo-700 tracking-tight"
              />
            </div>
          </div>

        </div>
      </div>

      <div class="wp-card">
        <div class="card-header border-b border-slate-100 bg-slate-50/20 px-6 py-4.5">
          <h3 class="font-extrabold text-sm tracking-tight text-slate-800">Membership</h3>
        </div>
        <div class="card-body p-6 divide-y divide-slate-100">

          <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5 first:pt-0">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
              Registration
            </span>
            <div class="md:col-span-3 flex items-center gap-3">
              <label class="wp-toggle">
                <input
                  type="checkbox"
                  checked={props.membershipOpen()}
                  onChange={(e) => props.setMembershipOpen(e.currentTarget.checked)}
                />
                <span class="toggle-slider"></span>
              </label>
              <span class="text-xs font-semibold text-slate-600">Allow anyone to register</span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-4 py-5 last:pb-0">
            <label for="default-role" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
              Default Role
            </label>
            <div class="md:col-span-3">
              <select
                id="default-role"
                value={props.defaultRole()}
                onChange={(e) => props.setDefaultRole(e.target.value)}
                class="text-xs font-semibold bg-[#f8fafc] border border-slate-200 hover:bg-slate-50 px-3.5 py-2.5 rounded-xl outline-none cursor-pointer transition-colors"
              >
                <option value="Subscriber">Subscriber</option>
                <option value="Contributor">Contributor</option>
                <option value="Author">Author</option>
                <option value="Editor">Editor</option>
                <option value="Administrator">Administrator</option>
              </select>
            </div>
          </div>

        </div>
      </div>

      <div class="wp-card">
        <div class="card-header border-b border-slate-100 bg-slate-50/20 px-6 py-4.5">
          <h3 class="font-extrabold text-sm tracking-tight text-slate-800">Permalinks</h3>
        </div>
        <div class="card-body p-6 space-y-5">
          <p class="text-xs text-slate-500 leading-relaxed font-semibold max-w-xl">
            Configure URL structures for your content. Clean URLs improve SEO and discoverability.
          </p>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-2">
            <label
              class="flex items-start gap-3.5 p-4 border rounded-xl hover:bg-slate-55 relative cursor-pointer overflow-hidden transition-all duration-200"
              classList={{
                'border-indigo-650 bg-indigo-50/10 ring-1 ring-indigo-100': props.permalinkStructure() === 'plain',
                'border-slate-200 bg-white hover:border-slate-300': props.permalinkStructure() !== 'plain'
              }}
            >
              <input
                type="radio"
                name="permalink-choice"
                checked={props.permalinkStructure() === 'plain'}
                onChange={() => props.setPermalinkStructure('plain')}
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
                'border-indigo-650 bg-indigo-50/10 ring-1 ring-indigo-100': props.permalinkStructure() === 'post-name',
                'border-slate-200 bg-white hover:border-slate-300': props.permalinkStructure() !== 'post-name'
              }}
            >
              <input
                type="radio"
                name="permalink-choice"
                checked={props.permalinkStructure() === 'post-name'}
                onChange={() => props.setPermalinkStructure('post-name')}
                class="mt-1 accent-indigo-600 scale-105 cursor-pointer"
              />
              <div class="space-y-0.5 select-none">
                <span class="text-xs font-bold text-slate-900 block flex items-center gap-1.5">
                  Post Name
                  <span class="text-[9px] font-bold px-1 rounded bg-indigo-100 text-indigo-700">RECOMMENDED</span>
                </span>
                <span class="text-[10px] text-indigo-650 font-mono block font-semibold">/sample-post/</span>
              </div>
            </label>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end pt-3">
        <button
          type="submit"
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs tracking-tight px-6 py-3.5 rounded-xl transition-all cursor-pointer shadow-md shadow-indigo-100/40 hover:scale-[1.02] active:scale-95 duration-200"
        >
          Save Changes
        </button>
      </div>

    </form>
  );
}
