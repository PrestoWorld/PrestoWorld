import React, { useState } from 'react';
import { Save } from 'lucide-react';

interface QuickDraftProps {
  content: string;
}

export const QuickDraft: React.FC<QuickDraftProps> = () => {
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');

  const handleSave = (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim() || !content.trim()) return;
    alert(`Đã lưu nháp: ${title}`);
    setTitle('');
    setContent('');
  };

  return (
    <form onSubmit={handleSave} className="space-y-3">
      <input
        type="text"
        placeholder="Tiêu đề nháp..."
        value={title}
        onChange={(e) => setTitle(e.target.value)}
        className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30"
      />
      <textarea
        placeholder="Nội dung..."
        value={content}
        onChange={(e) => setContent(e.target.value)}
        rows={4}
        className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 resize-none"
      />
      <div className="flex items-center justify-end gap-2">
        <button
          type="button"
          className="px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900"
        >
          Hủy
        </button>
        <button
          type="submit"
          className="flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700"
        >
          <Save className="w-3.5 h-3.5" /> Lưu Nháp
        </button>
      </div>
    </form>
  );
};