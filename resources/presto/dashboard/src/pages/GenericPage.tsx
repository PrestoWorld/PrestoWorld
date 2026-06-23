interface GenericPageProps {
  screenId: () => string;
}

export default function GenericPage(props: GenericPageProps) {
  const title = () => {
    const id = props.screenId();
    return id
      .replace(/-/g, ' ')
      .replace(/\b\w/g, (c) => c.toUpperCase());
  };

  return (
    <div class="space-y-6">
      <div>
        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">{title()}</h2>
        <p class="text-xs text-slate-500 font-semibold mt-1">
          This screen is coming soon. Data from the API will render here.
        </p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-12 flex flex-col items-center justify-center text-slate-400">
        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <p class="text-sm font-semibold">Screen: {props.screenId()}</p>
      </div>
    </div>
  );
}
