import { ArrowRight, Gamepad2 } from 'lucide-react';

type Props = { navigate: (page: 'home' | 'store' | 'cart' | 'orders') => void; navigateInfo: (slug:string)=>void; notify: (message: string) => void };

export default function MarketplaceFooter({ navigate, navigateInfo, notify }: Props) {
  return <footer className="footer">
    <div className="footer-main">
      <div className="footer-brand">
        <button className="brand" onClick={() => navigate('home')} aria-label="ameba home"><img src="/ameba-logo.svg" alt=""/></button>
        <p>More games. Better prices.<br/>Made for your next great play.</p>
      </div>
      <div className="footer-col"><b>SHOP</b><button onClick={() => navigate('store')}>All games</button><button onClick={() => navigate('store')}>PC games</button><button onClick={() => navigate('store')}>Console games</button></div>
      <div className="footer-col"><b>ameba</b><a href="/how-it-works" onClick={event => { event.preventDefault(); navigateInfo('how-it-works'); }}>How it works</a><a href="/buyer-protection" onClick={event => { event.preventDefault(); navigateInfo('buyer-protection'); }}>Buyer protection</a><a href="/help" onClick={event => { event.preventDefault(); navigateInfo('help'); }}>Help center</a><button onClick={() => navigate('orders')}>Your orders</button></div>
      <div className="footer-news"><span className="section-kicker">THE ameba PLAYER LETTER</span><b>Good deals, in your inbox.</b><span>New drops and player-picked prices.</span><form onSubmit={event => { event.preventDefault(); notify('Thanks for subscribing!'); }}><input type="email" placeholder="Your email address" required/><button aria-label="Subscribe"><ArrowRight size={16}/></button></form></div>
    </div>
    <div className="footer-bottom"><span>© 2026 ameba. MADE FOR PLAYERS.</span><span>Digital games, delivered instantly.</span><span><a href="/terms" onClick={event => { event.preventDefault(); navigateInfo('terms'); }}>Terms</a><i/><a href="/privacy" onClick={event => { event.preventDefault(); navigateInfo('privacy'); }}>Privacy Policy</a><i/><a href="/cookies" onClick={event => { event.preventDefault(); navigateInfo('cookies'); }}>Cookies</a></span></div>
  </footer>;
}
