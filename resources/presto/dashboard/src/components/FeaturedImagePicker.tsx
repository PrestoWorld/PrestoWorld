import { createSignal, For, Show, createEffect, onCleanup } from 'solid-js';
import { Image, Upload, X, Check, Loader } from 'lucide-solid';
import { fetchMedia, uploadMedia, type WPMediaItem } from '../api';

function formatSize(bytes: number): string {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

export default function FeaturedImagePicker() {
  const [isOpen, setIsOpen] = createSignal(false);
  const [tab, setTab] = createSignal<'library' | 'upload'>('library');
  const [items, setItems] = createSignal<WPMediaItem[]>([]);
  const [loading, setLoading] = createSignal(false);
  const [selectedUrl, setSelectedUrl] = createSignal('');
  const [uploading, setUploading] = createSignal(false);
  const [dragOver, setDragOver] = createSignal(false);

  let fileInputRef: HTMLInputElement | undefined;

  const currentUrl = () => {
    const input = document.querySelector<HTMLInputElement>('input[name="featured_image"]');
    return input ? input.value : '';
  };

  setSelectedUrl(currentUrl());

  const loadMedia = () => {
    setLoading(true);
    fetchMedia().then(setItems).catch(() => {}).finally(() => setLoading(false));
  };

  const openModal = () => {
    setIsOpen(true);
    setTab('library');
    setSelectedUrl(currentUrl());
    loadMedia();
  };

  const closeModal = () => {
    setIsOpen(false);
  };

  const handleBackdropKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && isOpen()) closeModal();
  };

  createEffect(() => {
    if (isOpen()) {
      window.addEventListener('keydown', handleBackdropKeyDown);
    } else {
      window.removeEventListener('keydown', handleBackdropKeyDown);
    }
    onCleanup(() => window.removeEventListener('keydown', handleBackdropKeyDown));
  });

  const selectImage = (url: string) => {
    setSelectedUrl(url);
    const input = document.querySelector<HTMLInputElement>('input[name="featured_image"]');
    if (input) {
      input.value = url;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
    const img = document.querySelector<HTMLImageElement>('.pw-feat-img');
    if (img) {
      img.src = url;
      img.style.display = '';
    }
    setIsOpen(false);
  };

  const removeImage = (e: MouseEvent) => {
    e.stopPropagation();
    setSelectedUrl('');
    const input = document.querySelector<HTMLInputElement>('input[name="featured_image"]');
    if (input) {
      input.value = '';
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
    const img = document.querySelector<HTMLImageElement>('.pw-feat-img');
    if (img) {
      img.src = '';
      img.style.display = 'none';
    }
  };

  const handleUpload = async (file: File) => {
    if (!file.type.startsWith('image/')) return;
    setUploading(true);
    try {
      const result = await uploadMedia(file);
      if (result.url) {
        selectImage(result.url);
      }
      setItems(prev => [result, ...prev]);
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

  const handleDragOver = (e: DragEvent) => { e.preventDefault(); setDragOver(true); };
  const handleDragLeave = () => setDragOver(false);

  const isImage = (item: WPMediaItem) => item.mimeType.startsWith('image/');

  return (
    <div class="pw-feat-picker">
      <Show when={selectedUrl()}>
        <div class="pw-feat-picker-preview" onClick={openModal}>
          <img src={selectedUrl()} class="pw-feat-picker-thumb" />
          <button
            onClick={removeImage}
            class="pw-feat-picker-remove"
            title="Remove featured image"
            type="button"
          >
            <X size={14} />
          </button>
        </div>
      </Show>

      <button
        type="button"
        onClick={openModal}
        class="button pw-feat-picker-btn"
      >
        <Image size={14} style="display:inline-block;vertical-align:middle;margin-right:4px;" />
        {selectedUrl() ? 'Replace Image' : 'Set featured image'}
      </button>

      <Show when={isOpen()}>
        <div
          class="pw-feat-modal-overlay"
          onClick={(e) => { if (e.target === e.currentTarget) closeModal(); }}
        >
          <div class="pw-feat-modal">
            <div class="pw-feat-modal-header">
              <h2>Featured Image</h2>
              <button onClick={closeModal} class="pw-feat-modal-close" type="button">
                <X size={18} />
              </button>
            </div>

            <div class="pw-feat-modal-tabs">
              <button
                type="button"
                classList={{ 'pw-feat-tab-active': tab() === 'library' }}
                onClick={() => setTab('library')}
              >
                <Image size={14} />
                Media Library
              </button>
              <button
                type="button"
                classList={{ 'pw-feat-tab-active': tab() === 'upload' }}
                onClick={() => setTab('upload')}
              >
                <Upload size={14} />
                Upload
              </button>
            </div>

            <div class="pw-feat-modal-body">
              <Show when={tab() === 'library'}>
                <Show when={!loading()} fallback={
                  <div class="pw-feat-loading">
                    <Loader size={24} class="pw-feat-spin" />
                    <span>Loading media...</span>
                  </div>
                }>
                  <Show when={items().length > 0} fallback={
                    <div class="pw-feat-empty">
                      <Image size={32} />
                      <p>No media items found. Upload an image first.</p>
                    </div>
                  }>
                    <div class="pw-feat-grid">
                      <For each={items().filter(isImage)}>
                        {(item) => (
                          <div
                            class="pw-feat-grid-item"
                            classList={{ 'pw-feat-selected': selectedUrl() === item.url }}
                            onClick={() => selectImage(item.url)}
                          >
                            <img src={item.thumbnailUrl || item.url} alt={item.title} loading="lazy" />
                            <Show when={selectedUrl() === item.url}>
                              <div class="pw-feat-check"><Check size={16} /></div>
                            </Show>
                            <div class="pw-feat-item-info">
                              <span class="pw-feat-item-name">{item.title}</span>
                              <span class="pw-feat-item-size">{formatSize(item.size)}</span>
                            </div>
                          </div>
                        )}
                      </For>
                    </div>
                  </Show>
                </Show>
              </Show>

              <Show when={tab() === 'upload'}>
                <div
                  class="pw-feat-upload-zone"
                  classList={{ 'pw-feat-dragover': dragOver() }}
                  onDrop={handleDrop}
                  onDragOver={handleDragOver}
                  onDragLeave={handleDragLeave}
                  onClick={() => fileInputRef?.click()}
                >
                  <Upload size={32} />
                  <p>Drop an image here or click to browse</p>
                  <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/*"
                    class="hidden"
                    onChange={handleFilePick}
                  />
                </div>
                <Show when={uploading()}>
                  <div class="pw-feat-uploading">
                    <Loader size={16} class="pw-feat-spin" />
                    <span>Uploading...</span>
                  </div>
                </Show>
              </Show>
            </div>
          </div>
        </div>
      </Show>
    </div>
  );
}
