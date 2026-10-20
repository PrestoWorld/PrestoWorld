import { createSignal } from 'solid-js';

export default function DiscussionSettingsPage() {
  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Discussion Settings</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">
          Settings for comments and discussion
        </p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <form class="space-y-4">
          <div class="border border-slate-200 rounded-md p-4">
            <div class="flex items-start space-x-3">
              <input type="checkbox" id="default_pingback_flag" class="mt-1" />
              <div class="space-y-0.5">
                <label class="text-xs font-bold text-slate-700" for="default_pingback_flag">
                  Attempt to notify any blogs linked to from the article
                </label>
                <p class="text-xs text-slate-500">
                  When a new article is published, WordPress will automatically attempt to notify all blogs linked to from the article (if the blog has pingbacks enabled).
                </p>
              </div>
            </div>
          </div>
          <div class="space-y-4">
            <div class="border border-slate-200 rounded-md p-4">
              <div class="flex items-start space-x-3">
                <input type="checkbox" id="default_comment_status" class="mt-1" checked />
                <div class="space-y-0.5">
                  <label class="text-xs font-bold text-slate-700" for="default_comment_status">
                    Allow people to submit comments on new articles
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="space-y-4">
            <div class="border border-slate-200 rounded-md p-4">
              <div class="flex items-start space-x-3">
                <input type="checkbox" id="comment_registration" class="mt-1" />
                <div class="space-y-0.5">
                  <label class="text-xs font-bold text-slate-700" for="comment_registration">
                    Users must be registered and logged in to comment
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Comment must be manually approved</label>
            <div class="flex items-start space-x-3">
              <input type="checkbox" id="comment_moderation" class="mt-1" />
              <div class="space-y-0.5">
                <label class="text-xs font-bold text-slate-700" for="comment_moderation">
                  An administrator must always approve the comment
                </label>
              </div>
            </div>
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Comment author must have a previously approved comment</label>
            <div class="flex items-start space-x-3">
              <input type="checkbox" id="comment_previously_approved" class="mt-1" />
              <div class="space-y-0.5">
                <label class="text-xs font-bold text-slate-700" for="comment_previously_approved">
                  Hold a comment in the queue if it is from a first-time commenter
                </label>
              </div>
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