<?php
declare(strict_types=1);
// Load a simple .env file without Composer; explicit process environment wins.
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if (!$line || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key); $value = trim(trim($value), "\"'");
        if (getenv($key) === false) { putenv($key . '=' . $value); $_ENV[$key] = $value; }
    }
}
$secureCookie = filter_var(getenv('SESSION_SECURE') ?: 'false', FILTER_VALIDATE_BOOLEAN);
session_set_cookie_params(['httponly'=>true, 'secure'=>$secureCookie, 'samesite'=>$secureCookie?'None':'Lax', 'path'=>'/']);
session_start();
header('Content-Type: application/json; charset=utf-8');
$allowedOrigin = getenv('APP_ORIGIN') ?: 'http://localhost:5173';
if (($_SERVER['HTTP_ORIGIN'] ?? '') === $allowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    $path = getenv('DB_PATH') ?: __DIR__ . '/../data/pixelmarket.sqlite';
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
    $pdo = new PDO('sqlite:' . $path, options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA foreign_keys=ON');
    $pdo->exec('CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT UNIQUE NOT NULL, password TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS products(id INTEGER PRIMARY KEY, slug TEXT UNIQUE NOT NULL, title TEXT NOT NULL, category TEXT NOT NULL, platform TEXT NOT NULL, region TEXT NOT NULL, description TEXT NOT NULL, image TEXT NOT NULL, popularity INTEGER DEFAULT 0)');
    $productColumns = array_column($pdo->query('PRAGMA table_info(products)')->fetchAll(), 'name');
    if (!in_array('genres', $productColumns, true)) $pdo->exec("ALTER TABLE products ADD COLUMN genres TEXT NOT NULL DEFAULT '[]'");
    $pdo->exec('CREATE TABLE IF NOT EXISTS wishlist(user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE, product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE, created_at TEXT DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(user_id,product_id))');
    $pdo->exec('CREATE TABLE IF NOT EXISTS offers(id INTEGER PRIMARY KEY, product_id INTEGER NOT NULL REFERENCES products(id), seller TEXT NOT NULL, price REAL NOT NULL, old_price REAL, stock INTEGER DEFAULT 20)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS keys_inventory(id INTEGER PRIMARY KEY, offer_id INTEGER NOT NULL REFERENCES offers(id), license_key TEXT UNIQUE NOT NULL, sold INTEGER DEFAULT 0)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS orders(id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id), total REAL NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS order_items(id INTEGER PRIMARY KEY, order_id INTEGER NOT NULL REFERENCES orders(id), product_title TEXT NOT NULL, quantity INTEGER NOT NULL, price REAL NOT NULL, license_key TEXT NOT NULL)');
    seed($pdo);
    return $pdo;
}

function seed(PDO $p): void {
    $games = json_decode(file_get_contents(__DIR__ . '/../database/demo-catalog.json'), true, 512, JSON_THROW_ON_ERROR);
    $productInsert = $p->prepare('INSERT OR IGNORE INTO products(slug,title,category,genres,platform,region,description,image,popularity) VALUES(?,?,?,?,?,?,?,?,?)');
    $productMetadataUpdate = $p->prepare('UPDATE products SET image=?,genres=? WHERE slug=?');
    $productFind = $p->prepare('SELECT id FROM products WHERE slug=?');
    $offerFind = $p->prepare('SELECT id FROM offers WHERE product_id=? AND seller=?');
    $offerInsert = $p->prepare('INSERT INTO offers(product_id,seller,price,old_price,stock) VALUES(?,?,?,?,?)');
    $keyCount = $p->prepare('SELECT COUNT(*) FROM keys_inventory WHERE offer_id=?');
    $keyInsert = $p->prepare('INSERT OR IGNORE INTO keys_inventory(offer_id,license_key) VALUES(?,?)');
    $sellers = [
        ['PixelVault', 0.00, 0, 18, 'PV'],
        ['GameHub', 1.65, null, 11, 'GH'],
        ['KeyHarbor', 3.20, null, 24, 'KH'],
    ];
    $p->beginTransaction();
    try {
        foreach ($games as $game) {
            $gameGenres = array_values(array_unique(array_filter($game['genres'] ?? [$game['category']], 'is_string')));
            $encodedGenres = json_encode($gameGenres, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $productInsert->execute([$game['slug'],$game['title'],$game['category'],$encodedGenres,$game['platform'],$game['region'],$game['description'],$game['image'],$game['popularity']]);
            // Refresh catalog metadata without replacing product IDs, offers, or user data.
            $productMetadataUpdate->execute([$game['image'],$encodedGenres,$game['slug']]);
            $productFind->execute([$game['slug']]);
            $productId = (int)$productFind->fetchColumn();
            foreach ($sellers as [$seller,$delta,$oldPrice,$stock,$sellerCode]) {
                $offerFind->execute([$productId,$seller]);
                $offerId = $offerFind->fetchColumn();
                if ($offerId === false) {
                    $offerInsert->execute([$productId,$seller,round((float)$game['price'] + $delta,2),$oldPrice === 0 ? $game['old_price'] : $oldPrice,$stock]);
                    $offerId = $p->lastInsertId();
                }
                $keyCount->execute([$offerId]);
                $existingKeys = (int)$keyCount->fetchColumn();
                for ($number = $existingKeys + 1; $number <= 25; $number++) {
                    $keyInsert->execute([$offerId,sprintf('DEMO-CAT-%s-%s-%03d',strtoupper($game['slug']),$sellerCode,$number)]);
                }
            }
        }
        $p->commit();
    } catch (Throwable $error) {
        $p->rollBack();
        throw $error;
    }
}
function jsonBody(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }
function respond(mixed $data, int $status=200): never { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function requireUser(): int { if (empty($_SESSION['user_id'])) respond(['error'=>'Please sign in to continue.'],401); return (int)$_SESSION['user_id']; }
function offersFor(PDO $p,int $id): array { $q=$p->prepare('SELECT id,seller,price,old_price,stock FROM offers WHERE product_id=? ORDER BY price'); $q->execute([$id]); return $q->fetchAll(); }
function productGenres(array $product): array {
    $genres = json_decode((string)($product['genres'] ?? '[]'), true);
    return is_array($genres) ? array_values(array_unique(array_filter($genres, 'is_string'))) : [];
}
function products(PDO $p,string $where='',array $args=[],string $sort='popularity'): array {
    $order=$sort==='price_asc'?'price ASC, p.popularity DESC':($sort==='price_desc'?'price DESC, p.popularity DESC':'p.popularity DESC');
    // Aggregate only available offers first, so filtering and displayed prices use
    // the same minimum in-stock EUR price for each product.
    $sql='SELECT p.*,available.price,available.old_price FROM products p JOIN (SELECT product_id,MIN(price) AS price,MAX(old_price) AS old_price FROM offers WHERE stock>0 GROUP BY product_id) available ON available.product_id=p.id '.($where?'WHERE '.$where.' ':'').'ORDER BY '.$order;
    $q=$p->prepare($sql); $q->execute($args); $rows=$q->fetchAll(); foreach($rows as &$r){$r['genres']=productGenres($r);$r['offers']=offersFor($p,(int)$r['id']);} return $rows;
}
$p=db(); $method=$_SERVER['REQUEST_METHOD']; $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); $path=preg_replace('#^/api#','',$path); $body=jsonBody();
if ($method==='GET' && $path==='/health') respond(['status'=>'ok','database'=>'sqlite']);
if ($method==='GET' && $path==='/home/banner') respond(['eyebrow'=>'THE NEXT LEVEL STARTS HERE','title'=>'Big worlds.\nBetter prices.','description'=>'The games you love, at prices you’ll love even more. Discover your next adventure today.','cta'=>'Explore best sellers']);
if ($method==='GET' && $path==='/home/bestsellers') respond(products($p,'p.popularity > 0',[],'popularity'));
if ($method==='GET' && $path==='/home/deals') respond(products($p,'available.old_price IS NOT NULL'));
if ($method==='GET' && $path==='/genres') {
  $available=[]; foreach($p->query('SELECT genres FROM products')->fetchAll() as $row) foreach(productGenres($row) as $genre) $available[$genre]=true;
  $genres=array_keys($available); natcasesort($genres); respond(array_values($genres));
}
if ($method==='GET' && $path==='/products') {
  $w=[];$a=[];$q=trim($_GET['q']??''); if($q){$w[]='(p.title LIKE ? OR p.category LIKE ?)';$a[]='%'.$q.'%';$a[]='%'.$q.'%';}
  if(!empty($_GET['platform'])){$w[]='p.platform=?';$a[]=$_GET['platform'];} if(!empty($_GET['region'])){$w[]='p.region=?';$a[]=$_GET['region'];}
  // PDO execute(array) binds values as strings. CAST the parameter so SQLite
  // compares numeric EUR prices rather than applying storage-class ordering to
  // the aggregate alias, which has no declared column affinity.
  if(isset($_GET['min'])&&$_GET['min']!==''){$w[]='available.price>=CAST(? AS REAL)';$a[]=(float)$_GET['min'];} if(isset($_GET['max'])&&$_GET['max']!==''){$w[]='available.price<=CAST(? AS REAL)';$a[]=(float)$_GET['max'];}
  $selectedGenres=$_GET['genre']??[]; if(!is_array($selectedGenres))$selectedGenres=[$selectedGenres]; $selectedGenres=array_values(array_unique(array_filter($selectedGenres,fn($genre)=>is_string($genre)&&$genre!=='')));
  if($selectedGenres){$genreConditions=[];foreach($selectedGenres as $genre){$genreConditions[]='EXISTS (SELECT 1 FROM json_each(p.genres) AS product_genre WHERE lower(product_genre.value)=lower(?))';$a[]=$genre;}$w[]='('.implode(' OR ',$genreConditions).')';}
  $sort=$_GET['sort']??'popularity';$rows=products($p,implode(' AND ',$w),$a,$sort);
  if($q && !$rows){$all=products($p);$limit=max(2,(int)(strlen($q)*0.35));$ranked=[];foreach($all as $row){if(!empty($_GET['platform'])&&$row['platform']!==$_GET['platform'])continue;if(!empty($_GET['region'])&&$row['region']!==$_GET['region'])continue;if(isset($_GET['min'])&&(float)$row['price']<(float)$_GET['min'])continue;if(isset($_GET['max'])&&(float)$row['price']>(float)$_GET['max'])continue;if($selectedGenres&&!array_intersect($selectedGenres,$row['genres']))continue;$title=strtolower($row['title']);$words=preg_split('/[^a-z0-9]+/',$title,-1,PREG_SPLIT_NO_EMPTY);$distance=levenshtein(strtolower($q),$title);foreach($words as $word)$distance=min($distance,levenshtein(strtolower($q),$word));if($distance<=$limit){$row['_distance']=$distance;$ranked[]=$row;}}usort($ranked,function($left,$right)use($sort){if($sort==='price_asc'&&(float)$left['price']!==(float)$right['price'])return (float)$left['price']<=>(float)$right['price'];if($sort==='price_desc'&&(float)$left['price']!==(float)$right['price'])return (float)$right['price']<=>(float)$left['price'];return $left['_distance']<=>$right['_distance']?:((int)$right['popularity']<=>(int)$left['popularity']);});$rows=array_map(function($r){unset($r['_distance']);return $r;},$ranked);}
  respond($rows);
}
if($method==='GET'&&preg_match('#^/products/([^/]+)$#',$path,$m)){$q=$p->prepare('SELECT p.*,MIN(o.price) price,MAX(o.old_price) old_price FROM products p JOIN offers o ON o.product_id=p.id WHERE p.slug=? GROUP BY p.id');$q->execute([$m[1]]);$r=$q->fetch();if(!$r)respond(['error'=>'Product not found'],404);$r['genres']=productGenres($r);$r['offers']=offersFor($p,(int)$r['id']);respond($r);}
if($method==='POST'&&$path==='/auth/register'){$name=trim($body['name']??'');$email=strtolower(trim($body['email']??''));$pw=$body['password']??'';if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pw)<8)respond(['error'=>'Enter a name, valid email, and password of at least 8 characters.'],422);try{$q=$p->prepare('INSERT INTO users(name,email,password) VALUES(?,?,?)');$q->execute([$name,$email,password_hash($pw,PASSWORD_DEFAULT)]);}catch(PDOException){respond(['error'=>'An account with that email already exists.'],409);}$_SESSION['user_id']=(int)$p->lastInsertId();$_SESSION['user_name']=$name;respond(['user'=>['name'=>$name,'email'=>$email]],201);}
if($method==='POST'&&$path==='/auth/login'){$q=$p->prepare('SELECT * FROM users WHERE email=?');$q->execute([strtolower(trim($body['email']??''))]);$u=$q->fetch();if(!$u||!password_verify($body['password']??'',$u['password']))respond(['error'=>'Email or password is incorrect.'],401);session_regenerate_id(true);$_SESSION['user_id']=(int)$u['id'];$_SESSION['user_name']=$u['name'];respond(['user'=>['name'=>$u['name'],'email'=>$u['email']]]);}
if($method==='POST'&&$path==='/auth/logout'){$_SESSION=[];session_destroy();$cookie=session_get_cookie_params();setcookie(session_name(),'',['expires'=>time()-42000,'path'=>$cookie['path'],'domain'=>$cookie['domain'],'secure'=>$cookie['secure'],'httponly'=>$cookie['httponly'],'samesite'=>$cookie['samesite']??'Lax']);respond(['ok'=>true]);}
if($method==='GET'&&$path==='/auth/me'){if(empty($_SESSION['user_id']))respond(['user'=>null]);$q=$p->prepare('SELECT name,email FROM users WHERE id=?');$q->execute([$_SESSION['user_id']]);respond(['user'=>$q->fetch()]);}
if($method==='GET'&&$path==='/wishlist'){$uid=requireUser();respond(products($p,'p.id IN (SELECT product_id FROM wishlist WHERE user_id=?)',[$uid]));}
if(($method==='POST'||$method==='DELETE')&&preg_match('#^/wishlist/([^/]+)$#',$path,$m)){$uid=requireUser();$slug=rawurldecode($m[1]);$q=$p->prepare('SELECT id FROM products WHERE slug=?');$q->execute([$slug]);$productId=$q->fetchColumn();if($productId===false)respond(['error'=>'Product not found'],404);if($method==='POST'){$q=$p->prepare('INSERT OR IGNORE INTO wishlist(user_id,product_id) VALUES(?,?)');$q->execute([$uid,(int)$productId]);respond(['ok'=>true]);}$q=$p->prepare('DELETE FROM wishlist WHERE user_id=? AND product_id=?');$q->execute([$uid,(int)$productId]);respond(['ok'=>true]);}
if($method==='POST'&&$path==='/account/password'){
  $uid=requireUser();$current=is_string($body['current_password']??null)?$body['current_password']:'';$next=is_string($body['new_password']??null)?$body['new_password']:'';$confirm=is_string($body['confirm_password']??null)?$body['confirm_password']:'';
  if(strlen($next)<8)respond(['error'=>'Your new password must be at least 8 characters.'],422);
  if($next!==$confirm)respond(['error'=>'The new password and confirmation do not match.'],422);
  $q=$p->prepare('SELECT password FROM users WHERE id=?');$q->execute([$uid]);$hash=$q->fetchColumn();
  if($hash===false||!password_verify($current,(string)$hash))respond(['error'=>'Your current password is incorrect.'],422);
  $q=$p->prepare('UPDATE users SET password=? WHERE id=?');$q->execute([password_hash($next,PASSWORD_DEFAULT),$uid]);respond(['ok'=>true]);
}
if($method==='POST'&&$path==='/account/delete'){
  $uid=requireUser();$current=is_string($body['current_password']??null)?$body['current_password']:'';
  if(($body['confirm']??false)!==true)respond(['error'=>'Confirm that you understand account deletion is permanent.'],422);
  $q=$p->prepare('SELECT password FROM users WHERE id=?');$q->execute([$uid]);$hash=$q->fetchColumn();
  if($hash===false||!password_verify($current,(string)$hash))respond(['error'=>'Your current password is incorrect.'],422);
  try{$p->beginTransaction();$q=$p->prepare('UPDATE users SET name=?,email=?,password=? WHERE id=?');$q->execute(['Deleted account','deleted-'.$uid.'-'.bin2hex(random_bytes(8)).'@accounts.invalid',password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),$uid]);$p->commit();}catch(Throwable $error){if($p->inTransaction())$p->rollBack();respond(['error'=>'The account could not be deleted. Please try again.'],500);}
  $_SESSION=[];session_destroy();$cookie=session_get_cookie_params();setcookie(session_name(),'',['expires'=>time()-42000,'path'=>$cookie['path'],'domain'=>$cookie['domain'],'secure'=>$cookie['secure'],'httponly'=>$cookie['httponly'],'samesite'=>$cookie['samesite']??'Lax']);respond(['ok'=>true]);
}
if($method==='POST'&&$path==='/checkout'){$uid=requireUser();$items=$body['items']??[];if(!$items)respond(['error'=>'Your cart is empty.'],422);$p->beginTransaction();try{$total=0;$selected=[];foreach($items as $item){$qty=max(1,min(10,(int)($item['quantity']??1)));$offerId=(int)($item['offer_id']??0);if($offerId>0){$q=$p->prepare('SELECT o.id,o.price,p.title FROM offers o JOIN products p ON p.id=o.product_id WHERE p.slug=? AND o.id=? LIMIT 1');$q->execute([$item['slug']??'',$offerId]);}else{$q=$p->prepare('SELECT o.id,o.price,p.title FROM offers o JOIN products p ON p.id=o.product_id WHERE p.slug=? ORDER BY o.price LIMIT 1');$q->execute([$item['slug']??'']);}$offer=$q->fetch();if(!$offer)throw new RuntimeException('An item or seller offer is unavailable.');$keys=$p->prepare('SELECT id,license_key FROM keys_inventory WHERE offer_id=? AND sold=0 LIMIT ?');$keys->bindValue(1,(int)$offer['id'],PDO::PARAM_INT);$keys->bindValue(2,$qty,PDO::PARAM_INT);$keys->execute();$available=$keys->fetchAll();if(count($available)<$qty)throw new RuntimeException('A product is out of stock.');$total+=(float)$offer['price']*$qty;$selected[]=[$offer,$qty,$available];}$q=$p->prepare('INSERT INTO orders(user_id,total) VALUES(?,?)');$q->execute([$uid,$total]);$oid=(int)$p->lastInsertId();$out=[];foreach($selected as [$offer,$qty,$keys]){foreach($keys as $key){$p->prepare('UPDATE keys_inventory SET sold=1 WHERE id=?')->execute([$key['id']]);$p->prepare('INSERT INTO order_items(order_id,product_title,quantity,price,license_key) VALUES(?,?,?,?,?)')->execute([$oid,$offer['title'],1,$offer['price'],$key['license_key']]);$out[]=['product'=>$offer['title'],'key'=>$key['license_key']];}}$p->commit();respond(['order_id'=>$oid,'total'=>round($total,2),'items'=>$out],201);}catch(Throwable $e){$p->rollBack();respond(['error'=>$e->getMessage()],422);}}
if($method==='GET'&&$path==='/orders'){$uid=requireUser();$q=$p->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC');$q->execute([$uid]);$orders=$q->fetchAll();foreach($orders as &$order){$it=$p->prepare('SELECT product_title,quantity,price,license_key FROM order_items WHERE order_id=?');$it->execute([$order['id']]);$order['items']=$it->fetchAll();}respond($orders);}
respond(['error'=>'Route not found'],404);
