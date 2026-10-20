import React, { useState } from 'react';
import { Search, Send, User, Phone, MoreVertical, MessageSquare, Sparkles } from 'lucide-react';

interface Msg { id: string; text: string; from: 'customer' | 'shop'; time: string; }
interface Conv { id: string; name: string; avatar: string; last: string; time: string; unread: number; channel: string; messages: Msg[]; }

const seed: Conv[] = [
  { id: 'c1', name: 'Nguyễn Văn A', avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=100', last: 'Chào shop, đang muốn hỏi về cuốn Sach Ngu Ngau...', time: 'Vừa xong', unread: 3, channel: 'Website', messages: [{ id: 'm1', text: 'Chào shop, đang muốn hỏi về cuốn Sach Ngu Ngau...', from: 'customer', time: '10:00' }] },
  { id: 'c2', name: 'Trần Thị B', avatar: 'https://images.unsplash.com/photo-1544005313-94ddf08d00e2?auto=format&fit=crop&q=80&w=100', last: 'Đơn hàng của tôi vẫn chưa được giao', time: '2 tiếng trước', unread: 0, channel: 'Facebook', messages: [{ id: 'm2', text: 'Đơn hàng của tôi vẫn chưa được giao', from: 'customer', time: '08:30' }] },
];

export const MessagesPage: React.FC = () => {
  const [convs, setConvs] = useState<Conv[]>(seed);
  const [activeId, setActiveId] = useState<string>(seed[0].id);
  const [input, setInput] = useState('');
  const active = convs.find((c) => c.id === activeId)!;

  const send = () => {
    if (!input.trim()) return;
    setConvs((prev) => prev.map((c) => c.id === activeId ? { ...c, messages: [...c.messages, { id: `m${Date.now()}`, text: input, from: 'shop', time: 'now' }], last: input } : c));
    setInput('');
  };

  return (
    <div className="flex h-[calc(100vh-120px)] bg-white rounded-xl border border-slate-200 overflow-hidden">
      <div className="w-80 border-r border-slate-200 flex flex-col">
        <div className="p-3 border-b border-slate-200 flex items-center gap-2">
          <MessageSquare className="w-5 h-5 text-blue-600" />
          <h2 className="font-bold text-slate-900">Tin Nhắn & CSKH</h2>
        </div>
        <div className="p-3">
          <div className="relative">
            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input className="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Tìm kiếm..." />
          </div>
        </div>
        <div className="flex-1 overflow-y-auto">
          {convs.map((c) => (
            <button key={c.id} onClick={() => setActiveId(c.id)} className={`w-full flex items-center gap-3 p-3 text-left hover:bg-slate-50 ${activeId === c.id ? 'bg-blue-50' : ''}`}>
              <img src={c.avatar} className="w-10 h-10 rounded-full object-cover" alt="" />
              <div className="flex-1 min-w-0">
                <div className="flex justify-between items-center">
                  <span className="font-medium text-sm truncate">{c.name}</span>
                  <span className="text-xs text-slate-400">{c.time}</span>
                </div>
                <p className="text-xs text-slate-500 truncate">{c.last}</p>
                <span className="text-[10px] text-blue-600">{c.channel}</span>
              </div>
              {c.unread > 0 && <span className="bg-blue-600 text-white text-xs rounded-full px-2 py-0.5">{c.unread}</span>}
            </button>
          ))}
        </div>
      </div>
      <div className="flex-1 flex flex-col">
        <div className="p-3 border-b border-slate-200 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <img src={active.avatar} className="w-9 h-9 rounded-full object-cover" alt="" />
            <div>
              <div className="font-medium text-sm">{active.name}</div>
              <div className="text-xs text-slate-400">{active.channel}</div>
            </div>
          </div>
          <MoreVertical className="w-5 h-5 text-slate-400" />
        </div>
        <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50">
          {active.messages.map((m) => (
            <div key={m.id} className={`flex ${m.from === 'shop' ? 'justify-end' : 'justify-start'}`}>
              <div className={`max-w-xs px-3 py-2 rounded-lg text-sm ${m.from === 'shop' ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200'}`}>{m.text}</div>
            </div>
          ))}
        </div>
        <div className="p-3 border-t border-slate-200 flex gap-2">
          <input value={input} onChange={(e) => setInput(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && send()} className="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Nhập tin nhắn..." />
          <button onClick={send} className="px-4 py-2 bg-blue-600 text-white rounded-lg"><Send className="w-4 h-4" /></button>
          <button className="px-3 py-2 border border-slate-200 rounded-lg"><Sparkles className="w-4 h-4 text-purple-600" /></button>
        </div>
      </div>
      <div className="w-72 border-l border-slate-200 p-4 hidden lg:block">
        <div className="flex items-center gap-2 mb-3"><User className="w-4 h-4" /><span className="font-medium text-sm">Thông tin khách</span></div>
        <div className="text-sm text-slate-600 space-y-2">
          <div className="flex items-center gap-2"><Phone className="w-4 h-4" /> 0909 xxx xxx</div>
        </div>
      </div>
    </div>
  );
};
