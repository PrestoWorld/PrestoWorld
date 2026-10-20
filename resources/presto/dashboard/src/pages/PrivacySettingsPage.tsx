import { createSignal } from 'solid-js';

export default function PrivacySettingsPage() {
  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Privacy Settings</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">
          Settings for privacy policy
        </p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="space-y-4">
          <label class="block text-xs font-bold text-slate-700 mb-1">Privacy Policy Page</label>
          <select class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Select --</option>
            <option value="1">Privacy Policy</option>
            <option value="2">Terms of Service</option>
          </select>
          <p class="mt-1 text-xs text-slate-500">
            This is the privacy policy page that will be shown to users when they register or comment.
          </p>
        </div>

        <div class="space-y-4">
          <label class="block text-xs font-bold text-slate-700 mb-1">Privacy Policy Guide</label>
          <p class="text-xs text-slate-500">
            Use the guide below to create a privacy policy for your site. You can edit the page directly or use the guide as a reference.
          </p>
          <div class="border border-slate-200 rounded-md p-4">
            <h3 class="text-xs font-bold text-slate-900 mb-2">What information do we collect?</h3>
            <p class="text-xs text-slate-500">We collect information from you when you register on our site, place an order, subscribe to our newsletter, respond to a survey or fill out a form.</p>
            <h3 class="text-xs font-bold text-slate-900 mt-2 mb-2">What do we use your information for?</h3>
            <p class="text-xs text-slate-500">Any of the information we collect from you may be used in one of the following ways:</p>
            <ul class="list-disc list-inside mt-2 text-xs text-slate-500">
              <li>To personalize your experience</li>
              <li>To improve our website</li>
              <li>To improve customer service</li>
              <li>To process transactions</li>
              <li>To administer a contest, promotion, or survey</li>
              <li>To send periodic emails</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
}