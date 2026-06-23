export default function UsersPage() {
  return (
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Users</h2>
          <p class="text-xs text-slate-500 font-semibold mt-1">Manage users for your site.</p>
        </div>
        <button class="text-[11px] font-bold bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl transition-colors">
          Add New User
        </button>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-xs">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="text-left font-semibold px-4 py-3 text-slate-600 w-8"><input type="checkbox" /></th>
              <th class="text-left font-semibold px-4 py-3 text-slate-600">Username</th>
              <th class="text-left font-semibold px-4 py-3 text-slate-600">Name</th>
              <th class="text-left font-semibold px-4 py-3 text-slate-600">Email</th>
              <th class="text-left font-semibold px-4 py-3 text-slate-600">Role</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b border-slate-100 hover:bg-slate-50">
              <td class="px-4 py-3"><input type="checkbox" /></td>
              <td class="px-4 py-3 font-semibold">admin</td>
              <td class="px-4 py-3">Administrator</td>
              <td class="px-4 py-3">admin@prestoworld.org</td>
              <td class="px-4 py-3">Administrator</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
}
