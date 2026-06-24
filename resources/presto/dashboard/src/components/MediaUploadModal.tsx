import { createSignal, For, Show, createEffect, onCleanup } from 'solid-js';
import { Upload, X, File, Image, Film, Music, Archive, Check, Clipboard, ExternalLink, Loader } from 'lucide-solid';
import { uploadMedia, type WPMediaItem } from '../api';

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

export interface UploadFileEntry {
  file: File;
  id: string;
  progress: number;
  status: 'pending' | 'uploading' | 'done' | 'error';
  result?: WPMediaItem;
  error?: string;
}

interface MediaUploadModalProps {
  isOpen: boolean;
  onClose: () => void;
  onUploadComplete?: (items: WPMediaItem[]) => void;
  accept?: string;
  multiple?: boolean;
}

export default function MediaUploadModal(props: MediaUploadModalProps) {
  const [dragOver, setDragOver] = createSignal(false);
  const [queue, setQueue] = createSignal<UploadFileEntry[]>([]);
  const [copiedId, setCopiedId] = createSignal<string | null>(null);

  let fileInputRef: HTMLInputElement | undefined;

  const reset = () => {
    setQueue([]);
    setDragOver(false);
    setCopiedId(null);
  };

  createEffect(() => {
    if (!props.isOpen) {
      reset();
    }
  });

  const addFiles = (files: FileList | File[]) => {
    const entries: UploadFileEntry[] = [];
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      entries.push({
        file,
        id: `${Date.now()}-${i}-${file.name}`,
        progress: 0,
        status: 'pending',
      });
    }
    setQueue(prev => [...prev, ...entries]);
    entries.forEach(entry => uploadEntry(entry));
  };

  const uploadEntry = async (entry: UploadFileEntry) => {
    setQueue(prev => prev.map(e => e.id === entry.id ? { ...e, status: 'uploading', progress: 0 } : e));
    try {
      const result = await uploadMedia(entry.file);
      setQueue(prev => prev.map(e => e.id === entry.id ? { ...e, status: 'done', progress: 100, result } : e));
      props.onUploadComplete?.([result]);
    } catch (err) {
      setQueue(prev => prev.map(e => e.id === entry.id ? { ...e, status: 'error', error: String(err) } : e));
    }
  };

  const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    setDragOver(false);
    if (e.dataTransfer?.files?.length) addFiles(Array.from(e.dataTransfer.files));
  };

  const handleDragOver = (e: DragEvent) => { e.preventDefault(); setDragOver(true); };
  const handleDragLeave = () => setDragOver(false);

  const handleFilePick = (e: Event) => {
    const input = e.currentTarget as HTMLInputElement;
    if (input.files?.length) addFiles(Array.from(input.files));
    input.value = '';
  };

  const removeEntry = (id: string) => {
    setQueue(prev => prev.filter(e => e.id !== id));
  };

  const copyUrl = async (url: string, id: string) => {
    try {
      await navigator.clipboard.writeText(url);
      setCopiedId(id);
      setTimeout(() => setCopiedId(null), 2000);
    } catch {}
  };

  const pendingCount = () => queue().filter(e => e.status === 'pending' || e.status === 'uploading').length;
  const doneCount = () => queue().filter(e => e.status === 'done').length;

  const hasItems = () => queue().length > 0;

  const handleBackdropKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.isOpen) props.onClose();
  };

  createEffect(() => {
    if (props.isOpen) {
      window.addEventListener('keydown', handleBackdropKeyDown);
    } else {
      window.removeEventListener('keydown', handleBackdropKeyDown);
    }
    onCleanup(() => window.removeEventListener('keydown', handleBackdropKeyDown));
  });

  return (
    <Show when={props.isOpen}>
      <div
        class="fixed inset-0 z-[200] flex items-center justify-center p-4 sm:p-6"
        onClick={(e) => { if (e.target === e.currentTarget) props.onClose(); }}
      >
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" />
        <div class="relative bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 animate-modalIn">
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                <Upload size={14} class="text-indigo-600" />
              </div>
              <div>
                <h2 class="text-sm font-bold text-slate-800">Upload Media</h2>
                <p class="text-[10px] text-slate-400 font-mono">JPG, PNG, GIF, WebP, SVG, MP4, MP3 & more</p>
              </div>
            </div>
            <button
              onClick={props.onClose}
              class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all cursor-pointer"
            >
              <X size={16} />
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-6 space-y-5">
            <div
              class="border-2 border-dashed rounded-xl p-10 text-center transition-all cursor-pointer"
              classList={{
                'border-indigo-400 bg-indigo-50/50 scale-[1.01]': dragOver(),
                'border-slate-200 bg-slate-50/30 hover:border-indigo-300 hover:bg-indigo-50/10': !dragOver(),
              }}
              onDrop={handleDrop}
              onDragOver={handleDragOver}
              onDragLeave={handleDragLeave}
              onClick={() => fileInputRef?.click()}
            >
              <div class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center">
                  <Upload size={24} class="text-indigo-400" />
                </div>
                <p class="text-sm font-bold text-slate-600">Drop files here or click to browse</p>
                <p class="text-[11px] text-slate-400 font-mono">Supports images, videos, audio, and documents up to 256MB</p>
              </div>
              <input
                ref={fileInputRef}
                type="file"
                class="hidden"
                accept={props.accept ?? '*'}
                multiple={props.multiple ?? true}
                onChange={handleFilePick}
              />
            </div>

            <Show when={hasItems()}>
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                    Upload Queue
                  </span>
                  <span class="text-[10px] font-mono text-slate-400">
                    {doneCount()} done &middot; {pendingCount()} pending
                  </span>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                  <For each={queue()}>
                    {(entry) => {
                      const Icon = mimeIcon(entry.file.type);
                      const isImage = entry.file.type.startsWith('image/');
                      return (
                        <div
                          class="flex items-center gap-3 p-3 rounded-xl border transition-all"
                          classList={{
                            'border-slate-100 bg-white': entry.status === 'pending',
                            'border-indigo-100 bg-indigo-50/30': entry.status === 'uploading',
                            'border-emerald-100 bg-emerald-50/30': entry.status === 'done',
                            'border-red-100 bg-red-50/30': entry.status === 'error',
                          }}
                        >
                          <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden shrink-0">
                            <Show when={isImage && entry.result?.thumbnailUrl} fallback={
                              <Icon size={18} class="text-slate-400" />
                            }>
                              <img src={entry.result!.thumbnailUrl} alt="" class="w-full h-full object-cover" />
                            </Show>
                          </div>

                          <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                              <span class="text-xs font-bold text-slate-700 truncate">{entry.file.name}</span>
                              <span class="text-[9px] font-mono text-slate-400 shrink-0">{formatSize(entry.file.size)}</span>
                            </div>

                            <Show when={entry.status === 'uploading'}>
                              <div class="mt-1.5 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-600 rounded-full transition-all duration-300" style={{ width: '60%' }} />
                              </div>
                            </Show>

                            <Show when={entry.status === 'error'}>
                              <p class="text-[10px] text-red-500 font-mono mt-0.5">{entry.error || 'Upload failed'}</p>
                            </Show>
                          </div>

                          <div class="flex items-center gap-1 shrink-0">
                            <Show when={entry.status === 'done' && entry.result}>
                              <button
                                onClick={() => copyUrl(entry.result!.url, entry.id)}
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all cursor-pointer"
                                title="Copy URL"
                              >
                                <Show when={copiedId() !== entry.id} fallback={<Check size={13} class="text-emerald-500" />}>
                                  <Clipboard size={13} />
                                </Show>
                              </button>
                              <a
                                href={entry.result!.url}
                                target="_blank"
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all"
                                title="Open in new tab"
                              >
                                <ExternalLink size={13} />
                              </a>
                            </Show>

                            <Show when={entry.status === 'pending' || entry.status === 'error'}>
                              <button
                                onClick={() => removeEntry(entry.id)}
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all cursor-pointer"
                                title="Remove"
                              >
                                <X size={13} />
                              </button>
                            </Show>

                            <Show when={entry.status === 'uploading'}>
                              <div class="w-7 h-7 flex items-center justify-center">
                                <Loader size={13} class="text-indigo-500 animate-spin" />
                              </div>
                            </Show>

                            <Show when={entry.status === 'done'}>
                              <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-emerald-50">
                                <Check size={13} class="text-emerald-600" />
                              </div>
                            </Show>
                          </div>
                        </div>
                      );
                    }}
                  </For>
                </div>
              </div>
            </Show>

            <Show when={!hasItems()}>
              <div class="flex items-center justify-center py-6">
                <div class="text-center">
                  <Image size={32} class="mx-auto text-slate-200 mb-2" />
                  <p class="text-xs text-slate-400 font-mono">No files selected yet</p>
                </div>
              </div>
            </Show>
          </div>

          <Show when={doneCount() > 0}>
            <div class="flex items-center justify-between px-6 py-3 border-t border-slate-100 bg-slate-50/50 rounded-b-2xl shrink-0">
              <span class="text-xs text-slate-500 font-mono">{doneCount()} file{doneCount() !== 1 ? 's' : ''} uploaded successfully</span>
              <button
                onClick={() => { reset(); props.onClose(); }}
                class="text-xs font-bold bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg transition-all cursor-pointer"
              >
                Done
              </button>
            </div>
          </Show>
        </div>
      </div>

    </Show>
  );
}
