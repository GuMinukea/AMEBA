export type Product = {
  id: number;
  slug: string;
  title: string;
  category: string;
  genres: string[];
  platform: string;
  region: string;
  description: string;
  image: string;
  popularity: number;
  price: number;
  old_price: number | null;
  offers: { id: number; seller: string; price: number; old_price: number | null; stock: number }[];
};
