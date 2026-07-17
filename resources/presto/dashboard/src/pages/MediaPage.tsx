import { createSignal, For, Show, onMount } from 'solid-js';
import { Image, Upload, Trash2, File, Film, Music, Archive, Download, Globe, HardDrive, CloudOff } from 'lucide-solid';
import { WPMediaItem } from '../api';
import { fetchMedia, uploadMedia, offloadMedia } from '../api';

function formatSize(bytes: number): string {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function mimeIcon(mime: string) {
  if (mime.startsWith('image/')) return Image;
  if (mime.startsWith('video/')) return Film;
  if (mime.startsWith('audio/')) return Music;
  if (mime.includes('zip') || mime.includes('rar') || mime.includes('tar')) return Archive;
  return File;
}

interface MediaPageProps {
  onOpenUploadModal?: () => void;
}

export default function MediaPage(props: MediaPageProps) {
  const [items, setItems] = createSignal<WPMediaItem[]>([]);
  const [loading, setLoading] = createSignal(true);
  const [uploading, setUploading] = createSignal(false);
  const [dragOver, setDragOver] = createSignal(false);

  const loadMedia = () => {
    setLoading(true);
    fetchMedia().then(setItems).catch(() => {}).finally(() => setLoading(false));
  };

  onMount(loadMedia);

  const handleUpload = async (file: File) => {
    if (!file.type.startsWith('image/') && !file.type.startsWith('video/') && !file.type.startsWith('audio/') && !file.type.startsWith('application/')) {
      return;
    }
    setUploading(true);
    try {
      const result = await uploadMedia(file);
      setItems([result, ...items()]);
    } catch {
      // ignore
    } finally {
      setUploading(false);
    }
  };

  const handleFilePick = (e: Event) => {
    const input = e.currentTarget as HTMLInputElement;
    const file = input.files?.[0];
    if (file) {
      handleUpload(file);
      input.value = '';
    }
  };

  const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    setDragOver(false);
    const file = e.dataTransfer?.files?.[0];
    if (file) handleUpload(file);
  };

  const handleDragOver = (e: DragEvent) => {
    e.preventDefault();
    setDragOver(true);
  };

  const handleDragLeave = () => setDragOver(false);

  const wordpressItems = () => items().filter(i => i.source === 'wordpress');
  const prestoItems = () => items().filter(i => i.source === 'presto');

  return (
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-white p-5 border border-slate-150/60 rounded-2xl shadow-sm">
        <div>
          <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Media Library</h2>
          <p class="text-xs text-slate-400 font-mono mt-0.5">{items().length} items &middot; {wordpressItems().length} WordPress &middot; {prestoItems().length} Presto</p>
        </div>
        <button
          onClick={() => props.onOpenUploadModal?.()}
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs tracking-tight px-5 py-3 rounded-xl flex items-center gap-2 cursor-pointer shadow-md shadow-indigo-200 transition-all"
        >
          <Upload size={14} strokeWidth={2.5} />
          <span>Upload File</span>
        </button>
      </div>

      <div
        class="border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer"
        classList={{
          'border-indigo-400 bg-indigo-50/30': dragOver(),
          'border-slate-200 bg-slate-50/30 hover:border-indigo-300 hover:bg-indigo-50/10': !dragOver(),
        }}
        onDrop={handleDrop}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onClick={() => props.onOpenUploadModal?.()}
      >
        <Upload size={28} class="mx-auto text-slate-300 mb-2" />
        <p class="text-xs font-bold text-slate-500">Drop files here or click to upload</p>
        <p class="text-[10px] text-slate-400 font-mono mt-1">Files are stored in Presto storage with ultra-low latency</p>
      </div>

      <Show when={uploading()}>
        <div class="flex items-center gap-3 p-4 bg-indigo-50 border border-indigo-100 rounded-xl">
          <div class="w-5 h-5 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
          <span class="text-xs font-bold text-indigo-700">Uploading...</span>
        </div>
      </Show>

      <Show when={!loading()} fallback={
        <div class="flex items-center justify-center min-h-[200px]">
          <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
        </div>
      }>
        <Show when={items().length > 0} fallback={
          <div class="text-center py-16">
            <Image size={40} class="mx-auto text-slate-200 mb-3" />
            <p class="text-sm font-bold text-slate-400">No media items yet</p>
            <p class="text-xs text-slate-300 mt-1">Upload images, videos, or documents above</p>
          </div>
        }>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <For each={items()}>
              {(item) => {
                const Icon = mimeIcon(item.mimeType);
                const isImage = item.mimeType.startsWith('image/');
                return (
                  <div class="wp-card overflow-hidden group relative bg-white border border-slate-150 rounded-xl hover:shadow-md transition-all">
                    <div class="aspect-square bg-slate-50 flex items-center justify-center overflow-hidden relative">
                      <Show when={isImage && item.thumbnailUrl} fallback={
                        <Icon size={32} class="text-slate-300" />
                      }>
                        <img
                          src={item.thumbnailUrl}
                          alt={item.alt || item.title}
                          class="w-full h-full object-cover"
                          loading="lazy"
                        />
                      </Show>

                      <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold font-mono uppercase tracking-wider shadow-sm"
                              classList={{
                                'bg-indigo-100 text-indigo-700': item.source === 'presto',
                                'bg-amber-100 text-amber-700': item.source === 'wordpress',
                              }}
                        >
                          {item.source}
                        </span>
                      </div>

                      <Show when={isImage && item.dimensions}>
                        <div class="absolute bottom-2 left-2 px-1.5 py-0.5 bg-black/50 text-white text-[9px] font-mono rounded opacity-0 group-hover:opacity-100 transition-opacity">
                          {item.dimensions!.width}x{item.dimensions!.height}
                        </div>
                      </Show>
                    </div>

                    <div class="p-2.5 space-y-1">
                      <p class="text-[11px] font-bold text-slate-800 truncate" title={item.title}>
                        {item.title}
                      </p>
                      <div class="flex items-center justify-between">
                        <span class="text-[9px] font-mono text-slate-400">{formatSize(item.size)}</span>
                        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                          <Show when={!item.offloaded && item.source === 'wordpress'}>
                            <button
                              onClick={() => offloadMedia(item.postId ?? item.id).then(() => loadMedia())}
                              class="p-1 text-amber-500 hover:text-indigo-600 transition-colors"
                              title="Offload to Presto storage"
                            >
                              <CloudOff size={11} />
                            </button>
                          </Show>
                          <Show when={item.url}>
                            <a href={item.url} target="_blank" class="p-1 text-slate-400 hover:text-indigo-600 transition-colors" title="Download">
                              <Download size={11} />
                            </a>
                          </Show>
                        </div>
                      </div>
                    </div>
                  </div>
                );
              }}
            </For>
          </div>

          <Show when={wordpressItems().length > 0}>
            <div class="mt-6 p-4 bg-amber-50/50 border border-amber-100 rounded-xl">
              <div class="flex items-center gap-2 mb-2">
                <Globe size={14} class="text-amber-600" />
                <span class="text-xs font-bold text-amber-800">WordPress Media</span>
                <span class="text-[9px] font-mono text-amber-600">{wordpressItems().length} items</span>
              </div>
              <p class="text-[10px] text-amber-700 leading-relaxed">
                These files were uploaded via WordPress. PrestoWorld serves them with low-latency offload
                when available in local storage. New uploads use Presto's native storage for zero-latency access.
              </p>
            </div>
          </Show>
        </Show>
      </Show>
    </div>
  );
}
