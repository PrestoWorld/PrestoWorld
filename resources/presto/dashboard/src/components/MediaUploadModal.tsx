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
        style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:16px;"
        onClick={(e) => { if (e.target === e.currentTarget) props.onClose(); }}
      >
        <div style="position:fixed;inset:0;background:rgba(15,23,42,0.5);" />
        <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:672px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,0.25);border:1px solid #e2e8f0;">
          <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid #eef2f6;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:12px;">
              <div style="width:32px;height:32px;border-radius:8px;background:#eef2ff;display:flex;align-items:center;justify-content:center;">
                <Upload size={14} color="#4f46e5" />
              </div>
              <div>
                <h2 style="margin:0;font-size:14px;font-weight:700;color:#1e293b;">Upload Media</h2>
                <p style="margin:0;font-size:10px;color:#94a3b8;font-family:monospace;">JPG, PNG, GIF, WebP, SVG, MP4, MP3 & more</p>
              </div>
            </div>
            <button
              onClick={props.onClose}
              style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;background:none;cursor:pointer;color:#94a3b8;"
              onMouseEnter={(e) => { e.currentTarget.style.color = '#475569'; e.currentTarget.style.background = '#f1f5f9'; }}
              onMouseLeave={(e) => { e.currentTarget.style.color = '#94a3b8'; e.currentTarget.style.background = 'none'; }}
            >
              <X size={16} />
            </button>
          </div>

          <div style="flex:1;overflow-y:auto;padding:24px;">
            <div style="display:flex;flex-direction:column;gap:20px;">

              <div
                style={{
                  border: '2px dashed',
                  borderRadius: '12px',
                  padding: '40px',
                  textAlign: 'center',
                  cursor: 'pointer',
                  transition: 'all 0.15s',
                  borderColor: dragOver() ? '#818cf8' : '#e2e8f0',
                  background: dragOver() ? '#eef2ff' : '#f8fafc',
                }}
                onDrop={handleDrop}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onClick={() => fileInputRef?.click()}
                onMouseEnter={(e) => { if (!dragOver()) { e.currentTarget.style.borderColor = '#a5b4fc'; e.currentTarget.style.background = '#f8fafc'; }}}
                onMouseLeave={(e) => { if (!dragOver()) { e.currentTarget.style.borderColor = '#e2e8f0'; e.currentTarget.style.background = '#f8fafc'; }}}
              >
                <div style={{ width: 48, height: 48, borderRadius: 12, background: '#eef2ff', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 8px' }}>
                  <Upload size={24} color="#818cf8" />
                </div>
                <p style={{ margin: 0, fontSize: 14, fontWeight: 700, color: '#475569' }}>Drop files here or click to browse</p>
                <p style={{ margin: '4px 0 0', fontSize: 11, color: '#94a3b8', fontFamily: 'monospace' }}>Supports images, videos, audio, and documents up to 256MB</p>
                <input
                  ref={fileInputRef}
                  type="file"
                  style="display:none"
                  accept={props.accept ?? '*'}
                  multiple={props.multiple ?? true}
                  onChange={handleFilePick}
                />
              </div>

              <Show when={hasItems()}>
                <div>
                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Upload Queue</span>
                    <span style="font-size:10px;font-family:monospace;color:#94a3b8;">
                      {doneCount()} done &middot; {pendingCount()} pending
                    </span>
                  </div>
                  <div style="display:flex;flex-direction:column;gap:8px;max-height:256px;overflow-y:auto;">
                    <For each={queue()}>
                      {(entry) => {
                        const Icon = mimeIcon(entry.file.type);
                        const isImage = entry.file.type.startsWith('image/');
                        let bgColor = '#fff';
                        let borderColor = '#e2e8f0';
                        if (entry.status === 'uploading') { bgColor = '#eef2ff'; borderColor = '#c7d2fe'; }
                        else if (entry.status === 'done') { bgColor = '#ecfdf5'; borderColor = '#a7f3d0'; }
                        else if (entry.status === 'error') { bgColor = '#fef2f2'; borderColor = '#fecaca'; }
                        return (
                          <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: 12, borderRadius: 12, border: '1px solid ' + borderColor, background: bgColor, transition: 'all 0.15s' }}>
                            <div style={{ width: 40, height: 40, borderRadius: 8, background: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'center', overflow: 'hidden', flexShrink: 0 }}>
                              <Show when={isImage && entry.result?.thumbnailUrl} fallback={
                                <Icon size={18} color="#94a3b8" />
                              }>
                                <img src={entry.result!.thumbnailUrl} alt="" style="width:100%;height:100%;object-fit:cover;" />
                              </Show>
                            </div>

                            <div style="flex:1;min-width:0;">
                              <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-size:12px;font-weight:700;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{entry.file.name}</span>
                                <span style="font-size:9px;font-family:monospace;color:#94a3b8;flex-shrink:0;">{formatSize(entry.file.size)}</span>
                              </div>

                              <Show when={entry.status === 'uploading'}>
                                <div style="margin-top:6px;height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden;">
                                  <div style="height:100%;background:#4f46e5;border-radius:999px;width:60%;transition:all 0.3s;" />
                                </div>
                              </Show>

                              <Show when={entry.status === 'error'}>
                                <p style="margin:2px 0 0;font-size:10px;color:#ef4444;font-family:monospace;">{entry.error || 'Upload failed'}</p>
                              </Show>
                            </div>

                            <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                              <Show when={entry.status === 'done' && entry.result}>
                                <button
                                  onClick={() => copyUrl(entry.result!.url, entry.id)}
                                  style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;background:none;cursor:pointer;color:#94a3b8;"
                                  onMouseEnter={(e) => { e.currentTarget.style.color = '#4f46e5'; e.currentTarget.style.background = '#eef2ff'; }}
                                  onMouseLeave={(e) => { e.currentTarget.style.color = '#94a3b8'; e.currentTarget.style.background = 'none'; }}
                                  title="Copy URL"
                                >
                                  <Show when={copiedId() !== entry.id} fallback={<Check size={13} color="#10b981" />}>
                                    <Clipboard size={13} />
                                  </Show>
                                </button>
                                <a
                                  href={entry.result!.url}
                                  target="_blank"
                                  style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none;"
                                  onMouseEnter={(e) => { e.currentTarget.style.color = '#4f46e5'; e.currentTarget.style.background = '#eef2ff'; }}
                                  onMouseLeave={(e) => { e.currentTarget.style.color = '#94a3b8'; e.currentTarget.style.background = 'none'; }}
                                  title="Open in new tab"
                                >
                                  <ExternalLink size={13} />
                                </a>
                              </Show>

                              <Show when={entry.status === 'pending' || entry.status === 'error'}>
                                <button
                                  onClick={() => removeEntry(entry.id)}
                                  style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;background:none;cursor:pointer;color:#94a3b8;"
                                  onMouseEnter={(e) => { e.currentTarget.style.color = '#ef4444'; e.currentTarget.style.background = '#fef2f2'; }}
                                  onMouseLeave={(e) => { e.currentTarget.style.color = '#94a3b8'; e.currentTarget.style.background = 'none'; }}
                                  title="Remove"
                                >
                                  <X size={13} />
                                </button>
                              </Show>

                              <Show when={entry.status === 'uploading'}>
                                <div style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                  <Loader size={13} color="#4f46e5" style="animation:spin 1s linear infinite;" />
                                </div>
                              </Show>

                              <Show when={entry.status === 'done'}>
                                <div style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#ecfdf5;">
                                  <Check size={13} color="#059669" />
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
                <div style="display:flex;align-items:center;justify-content:center;padding:24px 0;">
                  <div style="text-align:center;">
                    <Image size={32} color="#e2e8f0" style="margin:0 auto 8px;display:block;" />
                    <p style="margin:0;font-size:12px;color:#94a3b8;font-family:monospace;">No files selected yet</p>
                  </div>
                </div>
              </Show>
            </div>
          </div>

          <Show when={doneCount() > 0}>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 24px;border-top:1px solid #eef2f6;background:#f8fafc;border-radius:0 0 16px 16px;flex-shrink:0;">
              <span style="font-size:12px;color:#64748b;font-family:monospace;">{doneCount()} file{doneCount() !== 1 ? 's' : ''} uploaded successfully</span>
              <button
                onClick={() => { reset(); props.onClose(); }}
                style="font-size:12px;font-weight:700;background:#1e293b;color:#fff;padding:8px 16px;border-radius:8px;border:none;cursor:pointer;transition:all 0.15s;"
                onMouseEnter={(e) => e.currentTarget.style.background = '#334155'}
                onMouseLeave={(e) => e.currentTarget.style.background = '#1e293b'}
              >
                Done
              </button>
            </div>
          </Show>
        </div>
      </div>

      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </Show>
  );
}
