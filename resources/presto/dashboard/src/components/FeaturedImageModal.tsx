import { createSignal, createResource, For, Show } from 'solid-js';
import { fetchMedia, uploadMedia, type WPMediaItem } from '../api';

function formatSize(bytes: number): string {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

interface FeaturedImageModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSelect: (url: string) => void;
}

export default function FeaturedImageModal(props: FeaturedImageModalProps) {
  const [tab, setTab] = createSignal<'library' | 'upload'>('library');

  const [mediaItems] = createResource(() => props.isOpen && tab() === 'library', async () => {
    const items = await fetchMedia();
    return items.filter(i => i.mimeType.startsWith('image/'));
  });

  const [uploading, setUploading] = createSignal(false);

  const handleUpload = async (file: File) => {
    if (!file.type.startsWith('image/')) return;
    setUploading(true);
    try {
      const result = await uploadMedia(file);
      props.onSelect(result.url);
      props.onClose();
    } catch {
      // ignore
    } finally {
      setUploading(false);
    }
  };

  const handleFilePick = (e: Event) => {
    const input = e.currentTarget as HTMLInputElement;
    const file = input.files?.[0];
    if (file) handleUpload(file);
    input.value = '';
  };

  return (
    <Show when={props.isOpen}>
      <div
        style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:16px;"
        onClick={(e) => { if (e.target === e.currentTarget) props.onClose(); }}
      >
        <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" />
        <div style="position:relative;background:#fff;border-radius:8px;width:100%;max-width:640px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
          <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #dcdcde;">
            <h2 style="margin:0;font-size:16px;font-weight:600;">Featured Image</h2>
            <button
              onClick={props.onClose}
              style="background:none;border:none;cursor:pointer;color:#787c82;padding:4px 8px;font-size:18px;border-radius:4px;"
              onMouseEnter={(e) => e.currentTarget.style.color = '#1d2327'}
              onMouseLeave={(e) => e.currentTarget.style.color = '#787c82'}
            >&times;</button>
          </div>

          <div style="display:flex;border-bottom:1px solid #dcdcde;background:#f6f7f7;">
            <button
              onClick={() => setTab('library')}
              style={{
                flex: 1,
                padding: '10px 16px',
                border: 'none',
                background: tab() === 'library' ? '#fff' : 'transparent',
                cursor: 'pointer',
                fontSize: '13px',
                fontWeight: 500,
                color: tab() === 'library' ? '#2271b1' : '#787c82',
                borderBottom: tab() === 'library' ? '2px solid #2271b1' : '2px solid transparent',
                transition: 'all 0.1s',
              }}
            >Media Library</button>
            <button
              onClick={() => setTab('upload')}
              style={{
                flex: 1,
                padding: '10px 16px',
                border: 'none',
                background: tab() === 'upload' ? '#fff' : 'transparent',
                cursor: 'pointer',
                fontSize: '13px',
                fontWeight: 500,
                color: tab() === 'upload' ? '#2271b1' : '#787c82',
                borderBottom: tab() === 'upload' ? '2px solid #2271b1' : '2px solid transparent',
                transition: 'all 0.1s',
              }}
            >Upload File</button>
          </div>

          <div style="flex:1;overflow-y:auto;padding:16px 20px;min-height:300px;">
            <Show when={tab() === 'library'}>
              <Show when={!mediaItems.loading} fallback={
                <div style="display:flex;align-items:center;justify-content:center;padding:60px 20px;color:#787c82;font-size:13px;">Loading media...</div>
              }>
                <Show when={mediaItems() && mediaItems()!.length > 0} fallback={
                  <div style="display:flex;align-items:center;justify-content:center;padding:60px 20px;color:#787c82;font-size:13px;">No media items found. Upload an image first.</div>
                }>
                  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;">
                    <For each={mediaItems()}>
                      {(item) => {
                        const thumb = item.thumbnailUrl || item.url;
                        return (
                          <div
                            onClick={() => { props.onSelect(item.url); props.onClose(); }}
                            style="border:2px solid #dcdcde;border-radius:6px;overflow:hidden;cursor:pointer;background:#f6f7f7;"
                            onMouseEnter={(e) => e.currentTarget.style.borderColor = '#2271b1'}
                            onMouseLeave={(e) => e.currentTarget.style.borderColor = '#dcdcde'}
                          >
                            <img
                              src={thumb}
                              alt={item.title}
                              style="width:100%;aspect-ratio:1;object-fit:cover;display:block;"
                              loading="lazy"
                            />
                            <div style="padding:6px 8px;background:#fff;border-top:1px solid #f0f0f1;">
                              <span style="display:block;font-size:11px;font-weight:600;color:#3c434a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{item.title}</span>
                              <span style="display:block;font-size:10px;color:#787c82;font-family:monospace;">{formatSize(item.size)}</span>
                            </div>
                          </div>
                        );
                      }}
                    </For>
                  </div>
                </Show>
              </Show>
            </Show>

            <Show when={tab() === 'upload'}>
              <Show when={!uploading()} fallback={
                <div style="display:flex;align-items:center;gap:8px;padding:12px 16px;background:#f0f6fc;border-radius:6px;font-size:13px;color:#2271b1;">Uploading...</div>
              }>
                <div
                  style="border:2px dashed #dcdcde;border-radius:8px;padding:40px 20px;text-align:center;cursor:pointer;color:#787c82;transition:all 0.15s;"
                  onClick={() => {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.onchange = (e) => handleFilePick(e);
                    input.click();
                  }}
                  onMouseEnter={(e) => { e.currentTarget.style.borderColor = '#2271b1'; e.currentTarget.style.background = '#f0f6fc'; e.currentTarget.style.color = '#1d2327'; }}
                  onMouseLeave={(e) => { e.currentTarget.style.borderColor = '#dcdcde'; e.currentTarget.style.background = ''; e.currentTarget.style.color = '#787c82'; }}
                >
                  <p style="margin:8px 0 0;font-size:13px;">Drop an image here or click to browse</p>
                  <input type="file" accept="image/*" style="display:none" onChange={handleFilePick} />
                </div>
              </Show>
            </Show>
          </div>
        </div>
      </div>
    </Show>
  );
}
