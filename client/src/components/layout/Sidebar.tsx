const navItems = [
  'Overview',
  'Inventory',
  'Sales',
  'Purchases',
  'Products',
  'Suppliers',
  'Customers',
  'Reports',
  'Settings',
];

type SidebarProps = {
  open: boolean;
  onClose: () => void;
};

export default function Sidebar({ open, onClose }: SidebarProps) {
  return (
    <>
      <div
        className={`fixed inset-0 z-30 bg-black/50 transition-opacity lg:hidden ${
          open ? 'opacity-100' : 'pointer-events-none opacity-0'
        }`}
        onClick={onClose}
      />

      <aside
        className={`fixed inset-y-0 left-0 z-40 w-64 transform border-r border-white/5 bg-[#060814] text-slate-200 transition-transform duration-300 lg:static lg:z-auto lg:translate-x-0 ${
          open ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="flex h-full flex-col px-5 py-5">
          <div className="mb-8">
            <div className='text-[2.1rem] leading-none font-extrabold tracking-[-0.02em] text-white [font-family:"Darker_Grotesque",sans-serif]'>
              Kinetodesk.
            </div>
            <p className="mt-2 max-w-[180px] text-sm leading-5 text-slate-400">
              Norman store operations dashboard
            </p>
          </div>

          <nav className="space-y-1.5">
            {navItems.map((item, index) => {
              const active = index === 0;

              return (
                <button
                  key={item}
                  type="button"
                  className={`flex w-full items-center rounded-2xl px-4 py-2.5 text-left text-[13px] font-medium transition ${
                    active
                      ? 'bg-indigo-500/15 text-white ring-1 ring-indigo-400/25'
                      : 'text-slate-300 hover:bg-white/5 hover:text-white'
                  }`}
                >
                  {item}
                </button>
              );
            })}
          </nav>

          <div className="mt-auto rounded-3xl border border-white/8 bg-white/[0.03] p-4">
            <div className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
              Store
            </div>
            <div className="mt-2 text-base font-semibold text-white">Norman, Oklahoma</div>
            <p className="mt-1 text-sm text-slate-400">
              Single-location retail and supply operations
            </p>
          </div>
        </div>
      </aside>
    </>
  );
}