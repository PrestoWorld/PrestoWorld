import { createSignal, createResource, Switch, Match, Show, For, onMount } from 'solid-js';
import { fetchCategories, fetchTags, savePost, fetchPost, updatePost } from '../api';

interface PostEditorProps {
  screenId: () => string;
  postId?: () => number;
}

export default function PostEditorPage(props: PostEditorProps) {
  const isEdit = () => props.screenId() === 'post';

  const [title, setTitle] = createSignal('');
  const [content, setContent] = createSignal('');
  const [excerpt, setExcerpt] = createSignal('');
  const [slug, setSlug] = createSignal('');
  const [status, setStatus] = createSignal('draft');
  const [visibility, setVisibility] = createSignal('public');
  const [password, setPassword] = createSignal('');
  const [featuredImage, setFeaturedImage] = createSignal('');
  const [selectedCats, setSelectedCats] = createSignal<number[]>([]);
  const [tagInput, setTagInput] = createSignal('');
  const [tags, setTags] = createSignal<string[]>([]);
  const [saving, setSaving] = createSignal(false);
  const [message, setMessage] = createSignal('');

  const [categories] = createResource(fetchCategories);
  const [allTags] = createResource(fetchTags);

  onMount(() => {
    if (isEdit() && props.postId) {
      const pid = props.postId();
      if (pid > 0) {
        fetchPost(pid).then(data => {
          setTitle(data.title);
          setContent(data.content);
          setExcerpt(data.excerpt);
          setSlug(data.slug);
          setStatus(data.status);
          setVisibility(data.visibility);
          setPassword(data.password);
          setFeaturedImage(data.featured_image);
          setSelectedCats(data.categories);
          setTags(data.tags);
        }).catch(() => setMessage('Failed to load post'));
      }
    }
  });

  const toggleCat = (id: number) => {
    setSelectedCats(prev =>
      prev.includes(id) ? prev.filter(c => c !== id) : [...prev, id]
    );
  };

  const addTag = (e: KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      const val = tagInput().trim();
      if (val && !tags().includes(val)) {
        setTags([...tags(), val]);
      }
      setTagInput('');
    }
  };

  const removeTag = (t: string) => {
    setTags(tags().filter(tag => tag !== t));
  };

  const handleSaveDraft = async (e: Event) => {
    e.preventDefault();
    setSaving(true);
    setMessage('');
    try {
      const data: Record<string, unknown> = {
        title: title(),
        content: content(),
        excerpt: excerpt(),
        slug: slug(),
        status: 'draft',
        visibility: visibility(),
        password: password(),
        featured_image: featuredImage(),
        categories: selectedCats(),
        tags: tags().join(', '),
      };

      if (isEdit() && props.postId) {
        await updatePost(props.postId(), data);
        setMessage('Draft updated.');
      } else {
        const result = await savePost(data);
        window.location.hash = `#/post/${result.id}`;
        setMessage('Draft saved.');
      }
    } catch {
      setMessage('Error saving draft.');
    } finally {
      setSaving(false);
    }
  };

  const handlePublish = async (e: Event) => {
    e.preventDefault();
    setSaving(true);
    setMessage('');
    try {
      let finalStatus = status();
      if (visibility() === 'private') finalStatus = 'private';
      else finalStatus = 'publish';

      const data: Record<string, unknown> = {
        title: title(),
        content: content(),
        excerpt: excerpt(),
        slug: slug(),
        status: finalStatus,
        visibility: visibility(),
        password: password(),
        featured_image: featuredImage(),
        categories: selectedCats(),
        tags: tags().join(', '),
      };

      if (isEdit() && props.postId) {
        await updatePost(props.postId(), data);
        setMessage('Post updated.');
      } else {
        const result = await savePost(data);
        window.location.hash = `#/post/${result.id}`;
        setMessage('Post published.');
      }
    } catch {
      setMessage('Error publishing post.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div>
      <style>{`
        .pw-editor-wrap { display:flex; gap:20px; }
        .pw-editor-main { flex:1; min-width:0; }
        .pw-editor-side { width:280px; flex-shrink:0; }
        .pw-meta-box { background:#fff; border:1px solid #dcdcde; margin-bottom:12px; border-radius:4px; }
        .pw-meta-box-title { padding:8px 12px; font-size:13px; font-weight:600; border-bottom:1px solid #dcdcde; cursor:default; }
        .pw-meta-box-inside { padding:8px 12px; font-size:12px; }
        .pw-meta-box-inside label { display:block; margin-bottom:4px; color:#2c3338; font-weight:500; }
        .pw-meta-box-inside input[type="text"],
        .pw-meta-box-inside input[type="password"],
        .pw-meta-box-inside select,
        .pw-meta-box-inside textarea { width:100%; box-sizing:border-box; }
        .pw-meta-box-inside input[type="checkbox"] { margin-right:4px; }
        .pw-publish-row { display:flex; gap:8px; margin-bottom:8px; }
        .pw-publish-row .button { flex:1; text-align:center; }
        .pw-tag-list { display:flex; flex-wrap:wrap; gap:4px; margin-top:4px; }
        .pw-tag-item { background:#f0f0f1; border:1px solid #dcdcde; border-radius:3px; padding:2px 8px; font-size:11px; display:inline-flex; align-items:center; gap:4px; }
        .pw-tag-item .remove { cursor:pointer; color:#b32d2e; font-weight:700; }
        .pw-cat-list { max-height:180px; overflow-y:auto; }
        .pw-cat-list label { font-weight:400; font-size:12px; }
        .pw-editor-title-box { background:#fff; border:1px solid #dcdcde; padding:16px; margin-bottom:12px; border-radius:4px; }
        .pw-editor-title-input { width:100%; padding:8px; font-size:16px; border:1px solid #8c8f94; border-radius:4px; box-sizing:border-box; }
        .pw-editor-box { background:#fff; border:1px solid #dcdcde; padding:16px; margin-bottom:12px; border-radius:4px; }
        .pw-editor-box-label { display:block; font-weight:600; margin-bottom:4px; font-size:13px; }
        .pw-editor-textarea { width:100%; padding:8px; border:1px solid #8c8f94; border-radius:4px; font-family:monospace; box-sizing:border-box; }
        .pw-editor-hint { color:#787c82; font-size:12px; margin:4px 0 0; }
        .pw-feat-preview-wrap { margin-bottom:8px; display:inline-block; }
        .pw-feat-picker-thumb { max-width:100%; max-height:150px; display:block; border-radius:4px; border:1px solid #dcdcde; }
        .pw-feat-picker-btn { font-size:12px !important; }
      `}</style>
      <Show when={message()}>
        <div class="notice notice-success inline" style="margin-bottom:12px;">
          <p><strong>{message()}</strong></p>
        </div>
      </Show>

      <form id="post-editor-form" onSubmit={handlePublish}>
        <div class="pw-editor-wrap">
          <div class="pw-editor-main">
            <div class="pw-editor-title-box">
              <input
                type="text"
                value={title()}
                onInput={(e) => setTitle(e.currentTarget.value)}
                placeholder="Add title"
                class="pw-editor-title-input"
              />
            </div>

            <div class="pw-editor-box">
              <label class="pw-editor-box-label">Content</label>
              <textarea
                value={content()}
                onInput={(e) => setContent(e.currentTarget.value)}
                rows="20"
                class="pw-editor-textarea"
              />
            </div>

            <div class="pw-editor-box">
              <label class="pw-editor-box-label">Excerpt</label>
              <textarea
                value={excerpt()}
                onInput={(e) => setExcerpt(e.currentTarget.value)}
                rows="3"
                class="pw-editor-textarea"
              />
              <p class="pw-editor-hint">Excerpts are optional hand-crafted summaries of your content.</p>
            </div>
          </div>

          <div class="pw-editor-side">
            <div class="pw-meta-box">
              <div class="pw-meta-box-title">Publish</div>
              <div class="pw-meta-box-inside">
                <div class="pw-publish-row">
                  <button type="button" onClick={handleSaveDraft} class="button" disabled={saving()}>
                    {saving() ? 'Saving...' : 'Save Draft'}
                  </button>
                  <button type="submit" class="button button-primary" disabled={saving()}>
                    {saving() ? 'Saving...' : isEdit() ? 'Update' : 'Publish'}
                  </button>
                </div>

                <label>Status</label>
                <select value={status()} onChange={(e) => setStatus(e.target.value)} style="margin-bottom:8px;">
                  <option value="draft">Draft</option>
                  <option value="pending">Pending Review</option>
                  <option value="publish">Published</option>
                  <option value="private">Privately Published</option>
                </select>

                <label>Visibility</label>
                <div style="margin-bottom:8px;">
                  <label>
                    <input type="radio" name="visibility" value="public" checked={visibility() === 'public'} onClick={() => setVisibility('public')} />
                    Public
                  </label>
                  <br />
                  <label>
                    <input type="radio" name="visibility" value="password" checked={visibility() === 'password'} onClick={() => setVisibility('password')} />
                    Password protected
                  </label>
                  <br />
                  <label>
                    <input type="radio" name="visibility" value="private" checked={visibility() === 'private'} onClick={() => setVisibility('private')} />
                    Private
                  </label>
                  <Show when={visibility() === 'password'}>
                    <div style="margin-top:4px;">
                      <input
                        type="password"
                        value={password()}
                        onInput={(e) => setPassword(e.currentTarget.value)}
                        placeholder="Enter password"
                      />
                    </div>
                  </Show>
                </div>
              </div>
            </div>

            <div class="pw-meta-box">
              <div class="pw-meta-box-title">Slug</div>
              <div class="pw-meta-box-inside">
                <input
                  type="text"
                  value={slug()}
                  onInput={(e) => setSlug(e.currentTarget.value)}
                  placeholder="auto-generated"
                />
              </div>
            </div>

            <div class="pw-meta-box">
              <div class="pw-meta-box-title">Categories</div>
              <div class="pw-meta-box-inside">
                <div class="pw-cat-list">
                  <Switch>
                    <Match when={categories.loading}>
                      <p style="color:#787c82;">Loading...</p>
                    </Match>
                    <Match when={categories() && categories()!.length === 0}>
                      <p style="color:#787c82;">No categories found.</p>
                    </Match>
                    <Match when={categories() && categories()!.length > 0}>
                      {categories()!.map(cat => (
                        <label>
                          <input
                            type="checkbox"
                            checked={selectedCats().includes(cat.id)}
                            onClick={() => toggleCat(cat.id)}
                          />
                          {cat.name}
                        </label>
                      ))}
                    </Match>
                  </Switch>
                </div>
              </div>
            </div>

            <div class="pw-meta-box">
              <div class="pw-meta-box-title">Tags</div>
              <div class="pw-meta-box-inside">
                <div class="pw-tag-list">
                  <For each={tags()}>
                    {(tag) => (
                      <span class="pw-tag-item">
                        {tag}
                        <span class="remove" onClick={() => removeTag(tag)}>&times;</span>
                      </span>
                    )}
                  </For>
                </div>
                <input
                  type="text"
                  value={tagInput()}
                  onInput={(e) => setTagInput(e.currentTarget.value)}
                  onKeyDown={addTag}
                  placeholder="Type and press Enter to add"
                  style="font-size:12px;margin-top:4px;"
                />
                <p style="color:#787c82;font-size:11px;margin:4px 0 0;">Separate tags with commas.</p>
              </div>
            </div>

            <div class="pw-meta-box">
              <div class="pw-meta-box-title">Featured Image</div>
              <div class="pw-meta-box-inside">
                <Show when={featuredImage()}>
                  <div class="pw-feat-preview-wrap">
                    <img src={featuredImage()} class="pw-feat-picker-thumb" />
                  </div>
                </Show>
                <div class="pw-editor-side-actions">
                  <label class="button pw-feat-picker-btn" style="cursor:pointer;">
                    {featuredImage() ? 'Replace Image' : 'Set featured image'}
                    <input
                      type="file"
                      accept="image/*"
                      style="display:none"
                      onChange={async (e) => {
                        const file = e.currentTarget.files?.[0];
                        if (!file) return;
                        const form = new FormData();
                        form.append('file', file);
                        try {
                          const res = await fetch('/api/admin/media/upload', { method: 'POST', body: form });
                          const data = await res.json();
                          setFeaturedImage(data.url);
                        } catch {
                          setMessage('Upload failed');
                        }
                      }}
                    />
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
}
