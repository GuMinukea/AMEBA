import { Check, ChevronDown, Menu, Play, Search, ShoppingBag, UserRound, X } from 'lucide-react';
import type * as React from 'react';
import { useEffect, useRef, useState } from 'react';

type User = { name: string; email: string };
type Page = 'home' | 'store' | 'cart' | 'orders' | 'account' | 'wishlist';

type Props = {
  cartCount: number;
  mobileNav: boolean;
  user: User | null;
  query: string;
  setQuery: (value: string) => void;
  setMobileNav: (open: boolean) => void;
  navigate: (page: Page) => void;
  browsePlatform: (platform: string) => void;
  setPlatform: (platform: string) => void;
  setRegion: (region: string) => void;
  setMin: (price: string) => void;
  setMax: (price: string) => void;
  setSort: (sort: string) => void;
  setApplied: (query: string) => void;
  openAuth: (mode?: 'login' | 'register') => void;
  logout: () => void;
  navigateAccount: (page: 'account' | 'orders' | 'wishlist') => void;
  submitSearch: (event: React.FormEvent<HTMLFormElement>) => void;
};

export default function MarketplaceHeader(props: Props) {
  const { cartCount, mobileNav, user, query, setQuery, setMobileNav, navigate, browsePlatform, setPlatform, setRegion, setMin, setMax, setSort, setApplied, openAuth, logout, navigateAccount, submitSearch } = props;
  const [country, setCountry] = useState(() => localStorage.getItem('ameba-country') || 'Lithuania');
  const [currency, setCurrency] = useState<'EUR'|'USD'>(() => localStorage.getItem('ameba-currency') === 'USD' ? 'USD' : 'EUR');
  const [localeOpen, setLocaleOpen] = useState(false);
  const [accountOpen, setAccountOpen] = useState(false);
  const localeRef = useRef<HTMLDivElement>(null);
  const accountRef = useRef<HTMLDivElement>(null);
  useEffect(() => { if (!localeOpen) return; const onKey = (event: KeyboardEvent) => { if (event.key === 'Escape') setLocaleOpen(false); }; const onOutside = (event: PointerEvent) => { if (!localeRef.current?.contains(event.target as Node)) setLocaleOpen(false); }; window.addEventListener('keydown', onKey); document.addEventListener('pointerdown', onOutside); return () => { window.removeEventListener('keydown', onKey); document.removeEventListener('pointerdown', onOutside); }; }, [localeOpen]);
  useEffect(() => { if (!accountOpen) return; const onKey = (event: KeyboardEvent) => { if (event.key === 'Escape') setAccountOpen(false); }; const onOutside = (event: PointerEvent) => { if (!accountRef.current?.contains(event.target as Node)) setAccountOpen(false); }; window.addEventListener('keydown', onKey); document.addEventListener('pointerdown', onOutside); return () => { window.removeEventListener('keydown', onKey); document.removeEventListener('pointerdown', onOutside); }; }, [accountOpen]);
  const chooseCountry = (value: string) => { setCountry(value); localStorage.setItem('ameba-country', value); window.dispatchEvent(new Event('ameba-country-change')); setLocaleOpen(false); };
  const chooseCurrency = (value: 'EUR'|'USD') => { setCurrency(value); localStorage.setItem('ameba-currency', value); window.dispatchEvent(new Event('ameba-currency-change')); };
  const browse = (platform = '', max = '', sort = 'popularity') => { setApplied(''); setQuery(''); setPlatform(platform); setRegion(''); setMin(''); setMax(max); setSort(sort); setMobileNav(false); navigate('store'); };
  return <>
    <header className="header">
      <div className="header-main">
        <button className="mobile-menu icon-btn" onClick={() => setMobileNav(!mobileNav)} aria-label="Toggle navigation">{mobileNav ? <X/> : <Menu/>}</button>
        <button className="brand" onClick={() => navigate('home')} aria-label="ameba home"><img src="/ameba-logo.svg" alt=""/></button>
        <form className="search" onSubmit={submitSearch}>
          <Search size={19}/><input value={query} onChange={event => setQuery(event.target.value)} placeholder="Search games and more" aria-label="Search games"/>
        </form>
        <div className="market-locale" ref={localeRef}>
          <button className="locale-trigger" onClick={() => setLocaleOpen(!localeOpen)} aria-expanded={localeOpen} aria-haspopup="dialog">{country} <ChevronDown size={12}/></button>
          <span className="locale-divider">|</span>
          <label className="currency-control" aria-label="Currency"><select value={currency} onChange={event => chooseCurrency(event.target.value as 'EUR'|'USD')}><option value="EUR">EUR</option><option value="USD">USD</option></select></label>
          {localeOpen && <><section className="locale-panel" role="dialog" aria-label="Choose your country"><div className="locale-panel-head"><b>Choose your country</b><button onClick={() => setLocaleOpen(false)} aria-label="Close"><X size={16}/></button></div><div className="country-options">{['Lithuania','Germany','France','Spain','Italy','Netherlands','Poland','Portugal','Sweden','Finland','Ireland','Belgium','Austria','Denmark','United Kingdom','Norway','Switzerland','Estonia','Latvia','Czechia','Greece','United States','Canada'].map(name=><button key={name} className={country===name?'country-selected':''} onClick={() => chooseCountry(name)}>{name}{country===name&&<span aria-label="Selected"><Check size={14}/></span>}</button>)}</div></section></>}
        </div>
        <div className="header-actions">
          <div className="header-auth-actions">
            {user ? <div className="account-menu" ref={accountRef}>
              <button className="account-trigger" aria-haspopup="menu" aria-expanded={accountOpen} onClick={() => setAccountOpen(open => !open)}><UserRound size={15}/><span>{user.name}</span><ChevronDown size={13}/></button>
              {accountOpen && <div className="account-dropdown" role="menu">
                <button role="menuitem" onClick={() => { setAccountOpen(false); navigateAccount('account'); }}>My Account</button>
                <button role="menuitem" onClick={() => { setAccountOpen(false); navigateAccount('orders'); }}>Orders</button>
                <button role="menuitem" onClick={() => { setAccountOpen(false); navigateAccount('wishlist'); }}>My Wishlist</button>
                <button role="menuitem" onClick={() => { setAccountOpen(false); void logout(); }}>Log Out</button>
              </div>}
            </div> : <><button className="auth-header-login" onClick={() => openAuth('login')}>LOG IN</button><span className="auth-divider" aria-hidden="true">|</span><button className="auth-header-register" onClick={() => openAuth('register')}>REGISTER</button></>}
          </div>
          <button className="cart-button" onClick={() => navigate('cart')}><ShoppingBag size={19}/><span>Cart</span>{cartCount>0&&<b aria-label={`${cartCount} items in cart`}>{cartCount}</b>}</button>
        </div>
      </div>
      <nav className={`nav ${mobileNav ? 'nav-open' : ''}`} aria-label="Game categories">
        <button className="nav-category" onClick={() => browse()}><Play size={15} fill="currentColor"/> All Games</button>
        <button onClick={() => browse('', '10', 'price_asc')}>Cheap Games</button>
        <button onClick={() => browsePlatform('PC')}>PC Games</button>
        <button onClick={() => browsePlatform('PlayStation 5')}>PlayStation</button>
        <button onClick={() => browsePlatform('Xbox Series X|S')}>Xbox</button>
        <button onClick={() => browsePlatform('Nintendo Switch')}>Nintendo</button>
        <div className="nav-spacer"/>
      </nav>
    </header>
  </>;
}

