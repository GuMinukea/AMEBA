import { ArrowRight, ChevronRight, CirclePercent, Gamepad2, Joystick, Monitor, Play } from 'lucide-react';
import type { Product } from './types';

type Props = {
  banner: { eyebrow?: string; title?: string; description?: string; cta?: string } | null;
  featured: Product | undefined;
  navigate: (page: 'home' | 'store' | 'cart' | 'orders') => void;
  openProduct: (product: Product) => void;
  setPlatform: (platform: string) => void;
  browsePlatform: (platform: string) => void;
};

export default function HomePage({ banner, featured, navigate, openProduct, setPlatform, browsePlatform }: Props) {
  const toPlatform = (platform: string) => browsePlatform(platform);
  const titleLines = (banner?.title || 'Big worlds.\nBetter prices.').replace(/\\n/g, '\n').split('\n');
  return <div className="home-page">
    <section className="home-campaign">
      <div className="campaign-art" style={featured ? { backgroundImage: `linear-gradient(90deg, #22104b 0%, rgba(34,16,75,.91) 35%, rgba(34,16,75,.12) 100%), url(${featured.image})` } : undefined}/>
      <div className="campaign-copy">
        <h1>{titleLines.map((line, index) => <span className="campaign-title-line" key={index}>{line}</span>)}</h1>
        <p>{banner?.description || 'Find your next game at a price worth playing.'}</p>
        <button className="campaign-cta" onClick={() => navigate('store')}>{banner?.cta || 'Shop games'} <ArrowRight size={16}/></button>
      </div>
      {featured && <button className="campaign-product" onClick={() => openProduct(featured)}>
        <b>{featured.title}</b><span>From <strong>€{Number(featured.price).toFixed(2)}</strong></span>
      </button>}
    </section>

    <section className="market-shortcuts" aria-label="Shop by category">
      <button onClick={() => { setPlatform(''); navigate('store'); }}><span><Gamepad2/></span><b>All games</b></button>
      <button onClick={() => toPlatform('PC')}><span><Monitor/></span><b>PC</b></button>
      <button onClick={() => toPlatform('PlayStation 5')}><span className="shortcut-play"><Play/></span><b>PlayStation</b></button>
      <button onClick={() => toPlatform('Xbox Series X|S')}><span className="shortcut-xbox"><Gamepad2/></span><b>Xbox</b></button>
      <button onClick={() => toPlatform('Nintendo Switch')}><span><Joystick/></span><b>Nintendo</b></button>
      <button onClick={() => navigate('store')}><span><CirclePercent/></span><b>Deals</b></button>
      <button className="shortcut-all" onClick={() => navigate('store')}>All offers <ChevronRight size={14}/></button>
    </section>
  </div>;
}
