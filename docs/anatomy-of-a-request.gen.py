# Generates anatomy-of-a-request.svg (plain SVG, Inkscape friendly: no CSS vars, real <text>)
from xml.sax.saxutils import escape as esc
W,H=1600,2130
out=[]
F="font-family:'DejaVu Sans',Arial,sans-serif"
C=dict(ink="#1f2933",muted="#52606d",line="#3e4c59",boot="#e8eef7",page="#eaf5ec",render="#fdf3e3",
       store="#f6e9f3",shut="#eceff1",box="#ffffff",db="#fff4d6",dbs="#c9a227",cache="#e3f2fd",caches="#1e88e5",
       accent="#c0392b",json="#7b1fa2",adm="#00796b")
def t(x,y,s,size=14,w="normal",fill=None,anchor="start",italic=False):
    st=f"{F};font-size:{size}px;font-weight:{w};fill:{fill or C['ink']}"+(";font-style:italic" if italic else "")
    out.append(f'<text x="{x}" y="{y}" xml:space="preserve" style="{st}" text-anchor="{anchor}">{esc(s)}</text>')
def box(x,y,w,h,title,lines=(),fill=None,stroke=None,tsize=15,lsize=12.5,dash=False,rx=8):
    d=' stroke-dasharray="6 4"' if dash else ''
    out.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{rx}" fill="{fill or C["box"]}" stroke="{stroke or C["line"]}" stroke-width="1.6"{d}/>')
    t(x+12,y+22,title,tsize,"bold")
    for i,l in enumerate(lines): t(x+12,y+42+i*17,l,lsize,fill=C["muted"])
def band(y,h,fill,label,num):
    out.append(f'<rect x="0" y="{y}" width="{W}" height="{h}" fill="{fill}"/>')
    out.append(f'<text transform="translate(30,{y+h/2}) rotate(-90)" style="{F};font-size:15px;font-weight:bold;fill:{C['line']}" text-anchor="middle">{esc(f"{num}  {label}")}</text>')
def arrow(x1,y1,x2,y2,color=None,dash=False,label=None,lx=None,ly=None,lanchor="start"):
    c=color or C["line"]; d=' stroke-dasharray="7 5"' if dash else ''
    mid={"#3e4c59":"a","#c0392b":"r","#7b1fa2":"p","#1e88e5":"b","#c9a227":"y","#00796b":"g"}[c]
    out.append(f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{c}" stroke-width="2"{d} marker-end="url(#m{mid})"/>')
    if label: t(lx if lx is not None else (x1+x2)/2+8, ly if ly is not None else (y1+y2)/2+4, label,12,fill=c,anchor=lanchor)
def poly(pts,color=None,dash=False,label=None,lx=0,ly=0,lanchor="start"):
    c=color or C["line"]; d=' stroke-dasharray="7 5"' if dash else ''
    mid={"#3e4c59":"a","#c0392b":"r","#7b1fa2":"p","#1e88e5":"b","#c9a227":"y","#00796b":"g"}[c]
    p=" ".join(f"{x},{y}" for x,y in pts)
    out.append(f'<polyline points="{p}" fill="none" stroke="{c}" stroke-width="2"{d} marker-end="url(#m{mid})"/>')
    if label: t(lx,ly,label,12,fill=c,anchor=lanchor)

# ---- bands
band(90,130,"#f5f7fa","Entry",0)
band(220,310,C["boot"],"Bootstrap — sparkbox.php",1)
band(550,220,C["page"],"Page construction",2)
band(770,340,"#e3f0e6","Input (POST → transaction → redirect)",3)
band(1110,640,C["render"],"Render — SparkPage::render()",4)
band(1750,190,C["shut"],"Shutdown",5)

# ---- header
t(24,44,"SparkBox — anatomy of a request",28,"bold")
t(24,72,"One HTTP request, top to bottom. Every request is short-lived: DB connections, sessions and output buffers end with it.",14,fill=C["muted"])

MX=420  # main column x
# entry
box(MX,110,360,80,"Browser request",["page script · admin2/ (path=…) · storage.php · JSON"])
box(MX+460,110,310,80,"Apache / .htaccess",["admin2: RewriteRule → index.php?path=/…","mod_expires off for storage"],fill="#ffffff")
arrow(MX+360,150,MX+460,150)

# bootstrap
arrow(MX+180,190,MX+180,250)
box(MX,250,360,110,"Early boot",["APP_PATH/config/boot.php","RateLimiter (REQUEST_THROTTLE_USERAGENT)","config.php · spark.php · debug.php","Spark::Initialize() · CACHE_PATH"])
box(MX,380,360,70,"DB config (lazy)",["config/dbconfig.php → DBManager::Add(DBConfig)","no connection opened yet"],fill=C["db"],stroke=C["dbs"])
arrow(MX+180,360,MX+180,380)
arrow(MX+180,450,MX+180,470)
box(MX,470,360,62,"App init",["config/include.php · class/ include path","config/app.php  (site level)"])
# storage branch decision
out.append(f'<polygon points="{MX+470},{491} {MX+540},{456} {MX+610},{491} {MX+540},{526}" fill="#ffffff" stroke="{C["line"]}" stroke-width="1.6"/>')
t(MX+540,488,"storage",12,"bold",anchor="middle"); t(MX+540,503,"request?",12,"bold",anchor="middle")
arrow(MX+360,491,MX+470,491)
arrow(MX+610,491,1135,491,C["accent"],label="yes",lx=MX+640,ly=482)
box(MX+30,290-38+0,0,0,"")  # noop spacer
# non-storage extras
box(60,300,310,150,"Non-storage requests",["zlib.output_compression = 1","DBDriverEvent observer (pool stats)","utils/language.php (tr())","HTMLHead / HTMLBody event registration","  (skipped for JSON requests)","shutdown fn: CacheFactory::CleanupPageCache"])
arrow(370,375,MX,310,dash=True)

# storage column (right)
SX=1135
box(SX,470,430,155,"sparkstorage.php  (exits after response)",["BeanDataRequest: route → cmd / class / id","ImageDataResponse (width,height clamped 64–3840)","FileDataResponse","CacheFactory::BeanCacheEntry","  CACHE_PATH/<BeanClass>/<id>/<entry>"],fill=C["store"],stroke=C["json"])
box(SX,660,430,215,"BeanDataResponse::send()",["1  authorizeAccess()","     .auth entry / auth_context → Authenticator","2  If-Modified-Since → 304","3  If-None-Match (ETag) → 304","4  cache hit → sendFile()  (X-Tag: SparkCache)","5  miss → loadBlob(): getByID, unserialize","     StorageObject → process() (ImageScaler,","     watermark) → store cache entry → send","Cache-Control: public, max-age=1y, immutable"],fill=C["store"],stroke=C["json"])
arrow(SX+215,625,SX+215,660,C["json"])
box(SX,930,430,96,"Bean cache invalidation",["DBTableBean insert/update/delete →","manageCache(): delete CACHE_PATH/<Class>/<id>/","(TIMESTAMP date_upload → strtotime → Last-Modified)"],fill=C["cache"],stroke=C["caches"])
arrow(SX+215,875,SX+215,930,C["caches"],dash=True)

# page construction
arrow(MX+180,532,MX+180,580)
box(MX,580,360,170,"new XPage()  (site class/pages)",["SparkPage::__construct()","  authorize(): Authenticator → AuthContext","    fail + authorized_access → redirect loginURL","    (JSON request → JSONResponse 'session expired')","  headInitialize()","page script builds the component tree:","  views, iterators (SelectQuery), forms"])
box(60,580,310,170,"admin2 / Module flow",["Module::Factory('admin','admin2/')","  → templates/modules/admin/init.php","Module::Response(): authorize()","SparkTemplatePage::initialize()","  Template::PathConfig(path)","    path/store.products.php → TemplateConfig","  LoadContent(): requireAuth → TemplateContent"],fill="#e0f2f1",stroke=C["adm"])
arrow(370,665,MX,665,C["adm"],dash=True)

# input processing
arrow(MX+180,750,MX+180,800)
box(MX,800,360,120,"BeanFormEditor::processInput()",["FormProcessor::process(form)","  submit value == form name ? else skip","InputForm → DataInput::loadPostData()","InputValidator::validate()"])
box(MX,940,360,150,"BeanTransactor::processForm()",["processBean(): DBDriver->transaction()","  InputProcessor::transactValue()","  bean insert/update (same $db)","  beforeCommit → commit → afterCommit","nested transaction() = SAVEPOINT","on success: redirect (Post/Redirect/Get)"],fill=C["db"],stroke=C["dbs"])
arrow(MX+180,920,MX+180,940)
poly([(MX+360,1060),(MX+410,1060),(MX+410,100),(MX+300,100),(MX+300,110)],C["accent"],dash=True,label="redirect → new GET",lx=MX+418,ly=1056)

# DB side box
box(60,800,310,260,"DBManager pool (per request)",["Driver(name): first idle driver","  prefer the driver inside a transaction","  busy (unbuffered result) → next / new","MAX_DRIVER_POOL_SIZE = 4","typical: 2 connections","  #1 iterator streaming result rows","  #2 tr() lookups / small queries","PDO: native prepares, unbuffered,","  ERRMODE_EXCEPTION, utf8mb4","session time_zone = date.timezone","disconnect: rollback open transaction"],fill=C["db"],stroke=C["dbs"])
arrow(370,1000,MX,1000,C["dbs"],dash=True)

# render
arrow(MX+180,1090,MX+180,1150)
box(MX,1150,360,200,"startRender()",["ob_start() — head buffer","RequestController::Process()","  first responder whose accept() is true","  JSON request → JSONResponse, exit","  page responder → process, maybe redirect","SessionAlertScript · Session::Close()","headFinalize(): title, meta description","ob_end_flush()  → <head> sent early"])
box(MX,1370,360,210,"renderImpl() — component tree",["for each Component::render():","  CacheFactory::CacheEntryOutput()","    PageCache hit & TTL ok → echo, skip","  startRender / renderImpl / finishRender","  miss → store buffer to PageCache","result views: IDataIterator (SelectQuery)","  each row → DataIteratorItem renderer","  tr() → 2nd DB connection"])
arrow(MX+180,1350,MX+180,1370)
box(MX,1600,360,80,"finishRender()",["</body></html>","ob_end_flush() → response complete"])
arrow(MX+180,1580,MX+180,1600)
box(60,1150,310,120,"JSON requests",["_REQUEST['JSONRequest'] is set","page + components are still built,","RequestController picks a JSONResponder","→ JSONResponse → exit"],fill="#f3e5f5",stroke=C["json"])
arrow(370,1210,MX,1210,C["json"],dash=True)
box(60,1370,310,210,"PageCache",["CACHE_PATH/PageCache/<Class>-<hash>","key: script basename + class + name","  + iterator SQL hash (result views)","PAGE_CACHE_TTL (default 86400 s)","PAGE_CACHE_ENABLED (default off)","component->setCacheable(true)","FileCacheEntry: flock SH/EX"],fill=C["cache"],stroke=C["caches"])
arrow(370,1460,MX,1460,C["caches"],dash=True)
box(SX,1150,430,160,"HTML output",["<head> flushed before the body is built:","  browser fetches CSS / JS in parallel","body buffered in 4 KB chunks","images → storage.php URLs","  (hrefImage / hrefThumb / hrefCrop)"])
arrow(MX+360,1250,SX,1250,dash=True)
poly([(SX+430,1230),(SX+448,1230),(SX+448,215),(MX+700,215),(MX+700,190)],C["json"],dash=True,label="image URLs → new storage requests",lx=SX+200,ly=1335,lanchor="middle")

# shutdown
arrow(MX+180,1680,MX+180,1790)
box(MX,1790,360,120,"Shutdown",["register_shutdown_function:","  CleanupPageCache() at most once per","  PAGE_CACHE_CLEANUP_DELTA (flock, non-blocking)","DBDriver::__destruct → disconnect()"])

# legend
LY=1975
t(24,LY,"Legend",16,"bold")
items=[(C["line"],False,"main flow"),(C["accent"],False,"exit / redirect"),(C["json"],True,"storage & JSON"),(C["dbs"],True,"database"),(C["caches"],True,"cache"),(C["adm"],True,"admin2 module")]
for i,(c,d,l) in enumerate(items):
    x=24+i*250
    arrow(x,LY+30,x+60,LY+30,c,dash=d); t(x+72,LY+35,l,13,fill=C["muted"])
t(24,LY+80,"sparkbox core (lib/)  →  sparkstore (lib/store, module 'store/')  →  site (class/, config/)",13,fill=C["muted"])
t(W-24,LY+80,"generated for the SparkBox README — edit freely in Inkscape",12,fill=C["muted"],anchor="end",italic=True)

defs="".join(f'<marker id="m{k}" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" fill="{v}"/></marker>' for k,v in dict(a=C["line"],r=C["accent"],p=C["json"],b=C["caches"],y=C["dbs"],g=C["adm"]).items())
svg=f'<?xml version="1.0" encoding="UTF-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">\n<defs>{defs}</defs>\n<rect width="{W}" height="{H}" fill="#ffffff"/>\n'+"\n".join(out)+"\n</svg>\n"
open("anatomy-of-a-request.svg","w").write(svg)
