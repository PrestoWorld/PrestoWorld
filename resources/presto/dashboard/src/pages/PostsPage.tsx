import { For, Show } from 'solid-js';
import { Search, Plus, MessageSquare, ToggleLeft, ToggleRight, Pencil, Trash2 } from 'lucide-solid';
import { WPPost } from '../types';

interface PostsPageProps {
  postSearch: () => string;
  setPostSearch: (v: string) => void;
  postCategoryFilter: () => string;
  setPostCategoryFilter: (v: string) => void;
  filteredPosts: () => WPPost[];
  togglePostStatus: (id: number) => void;
  handleDeletePost: (id: number, title: string) => void;
  setIsAddPostOpen: (v: boolean) => void;
  goTo: (screen: string, extra?: string) => void;
}

export default function PostsPage(props: PostsPageProps) {
  return (
    <div class="space-y-6">

      <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-white p-5 border border-slate-150/60 rounded-2xl shadow-sm">

        <div class="relative flex-grow max-w-md">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
            <Search size={14} />
          </span>
          <input
            type="text"
            placeholder="Search posts by title, category or tags..."
            value={props.postSearch()}
            onInput={(e) => props.setPostSearch(e.currentTarget.value)}
            class="w-full text-xs font-semibold bg-[#f8fafc] border border-slate-200 focus:border-indigo-650 rounded-xl pl-10 pr-4 py-3 outline-none transition-all focus:bg-white"
          />
        </div>

        <div class="flex items-center gap-3 flex-wrap">
          <select
            value={props.postCategoryFilter()}
            onChange={(e) => props.setPostCategoryFilter(e.target.value)}
            class="text-xs font-semibold bg-[#f8fafc] border border-slate-200 hover:bg-slate-50 px-3.5 py-3 rounded-xl outline-none cursor-pointer transition-colors"
          >
            <option value="All">All Categories</option>
            <option value="Development">Development</option>
            <option value="Performance">Performance</option>
            <option value="E-Commerce">E-Commerce</option>
            <option value="Design">Design</option>
            <option value="Security">Security</option>
          </select>

          <button
            onClick={() => props.goTo('post-new')}
            class="bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-extrabold text-xs tracking-tight px-5 py-3 rounded-xl flex items-center gap-2 cursor-pointer shadow-md shadow-indigo-200 transition-all duration-200 hover:scale-[1.02]"
          >
            <Plus size={14} strokeWidth={2.5} />
            <span>Write New Post</span>
          </button>
        </div>
      </div>

      <div class="wp-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="wp-styled-table whitespace-nowrap">
            <thead>
              <tr>
                <th class="w-[50%] min-w-[280px]">Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Status</th>
                <th class="text-center">Date</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <For each={props.filteredPosts()} fallback={
                <tr>
                  <td colspan="6" class="p-12 text-center text-xs font-bold text-slate-400 font-mono tracking-wider">
                    No matching posts found.
                  </td>
                </tr>
              }>
                {(post) => (
                  <tr>
                    <td>
                      <div class="space-y-1">
                        <span class="font-bold text-slate-800 text-[13px] leading-snug block hover:text-indigo-650 cursor-pointer transition-colors max-w-sm sm:max-w-md truncate" title={post.title}>
                          {post.title}
                        </span>
                        <div class="flex items-center gap-2.5 text-[10px] text-slate-400 font-semibold font-mono">
                          <span>ID: {post.id}</span>
                          <span class="text-slate-205">&bull;</span>
                          <span class="flex items-center gap-1">
                            <MessageSquare size={9} strokeWidth={2.5} class="text-slate-400" />
                            {post.commentsCount} Comments
                          </span>
                        </div>
                      </div>
                    </td>

                    <td>
                      <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center font-extrabold text-[9px] text-white uppercase shadow-sm"
                             classList={{
                               'bg-[#4ade80]': post.author === 'admin',
                               'bg-indigo-500': post.author === 'editor',
                               'bg-[#38bdf8]': post.author === 'contributor',
                               'bg-purple-500': post.author !== 'admin' && post.author !== 'editor' && post.author !== 'contributor'
                             }}
                        >
                          {post.author.slice(0, 2)}
                        </div>
                        <span class="font-bold text-slate-700 font-mono text-[11px]">{post.author}</span>
                      </div>
                    </td>

                    <td>
                      <span class="text-[10px] font-bold px-2.5 py-0.5 rounded border capitalize shadow-sm tracking-wide"
                            classList={{
                              'bg-indigo-50 text-indigo-700 border-indigo-100/70': post.category === 'Development',
                              'bg-emerald-50 text-emerald-700 border-emerald-100/70': post.category === 'Performance',
                              'bg-pink-50 text-pink-700 border-pink-100/70': post.category === 'E-Commerce',
                              'bg-purple-50 text-purple-700 border-purple-100/70': post.category === 'Design',
                              'bg-amber-50 text-amber-700 border-amber-100/70': post.category === 'Security',
                              'bg-slate-50 text-slate-600 border-slate-200': !['Development', 'Performance', 'E-Commerce', 'Design', 'Security'].includes(post.category)
                            }}
                      >
                        {post.category}
                      </span>
                    </td>

                    <td>
                      <span
                        class="wp-badge select-none"
                        classList={{
                          'badge-published': post.status === 'Published',
                          'badge-draft': post.status === 'Draft',
                          'badge-scheduled': post.status === 'Scheduled'
                        }}
                      >
                        {post.status}
                      </span>
                    </td>

                    <td class="text-center text-xs font-semibold text-slate-500 font-mono">
                      {post.date}
                    </td>

                    <td class="text-right">
                      <div class="flex items-center justify-end gap-1">
                        <button
                          onClick={() => props.togglePostStatus(post.id)}
                          class="p-2 rounded-lg border transition-all cursor-pointer active:scale-90 hover:scale-105"
                          classList={{
                            'text-emerald-600 hover:bg-emerald-50 border-transparent hover:border-emerald-100': post.status === 'Published',
                            'text-slate-400 hover:bg-slate-50 border-transparent hover:border-slate-200': post.status !== 'Published'
                          }}
                          title={post.status === 'Published' ? 'Unpublish' : 'Publish'}
                        >
                          <Show when={post.status === 'Published'} fallback={<ToggleLeft size={18} strokeWidth={2} />}>
                            <ToggleRight size={18} strokeWidth={2} />
                          </Show>
                        </button>
                        <button
                          onClick={() => props.goTo('post', String(post.id))}
                          class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg border border-transparent hover:border-indigo-100 transition-all cursor-pointer active:scale-90 hover:scale-105"
                          aria-label="Edit post"
                          title="Edit"
                        >
                          <Pencil size={13} strokeWidth={2.5} />
                        </button>
                        <button
                          onClick={() => props.handleDeletePost(post.id, post.title)}
                          class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg border border-transparent hover:border-rose-100 transition-all cursor-pointer active:scale-90 hover:scale-105"
                          aria-label="Delete post"
                          title="Delete"
                        >
                          <Trash2 size={13} strokeWidth={2.5} />
                        </button>
                      </div>
                    </td>

                  </tr>
                )}
              </For>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  );
}
