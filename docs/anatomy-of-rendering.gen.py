# Generates anatomy-of-a-request.svg (plain SVG, Inkscape friendly: no CSS vars, real <text>)
from xml.sax.saxutils import escape as esc
W,H=1600,2060
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
band(90,250,"#f5f7fa","Construction",0)
band(340,470,C["boot"],"Component::render()",1)
band(810,420,C["page"],"Result view — AbstractResultView",2)
band(1230,330,"#e3f0e6","Rows — renderItem()",3)
band(1560,330,C["render"],"Inside an item",4)

t(24,44,"SparkBox — anatomy of rendering (components and result views)",28,"bold")
t(24,72,"Components are built first and rendered later: construction registers head contents, render() writes HTML — or replays it from the PageCache.",14,fill=C["muted"])

MX=420
SX=1135
box(MX,110,360,110,"new SomeComponent()",["Component::__construct()","  CSS class from the PHP class chain","  (Component Container ItemView ...)","emits ComponentEvent::COMPONENT_CREATED"])
arrow(MX+180,220,MX+180,240)
box(MX,240,360,80,"Page script builds the tree",["Container::items()->append(...)","views get an IDataIterator + item renderer","nothing is queried or written yet"])
box(SX,110,430,110,"HTMLHead observer",["IHeadContents: requiredStyle() / requiredScript()","  → addCSS / addJS once per URL","CSS/JS of every component reaches <head>","  before the body is rendered"],fill="#f3e5f5",stroke=C["json"])
box(SX,240,430,80,"HTMLBody observer",["IPageComponent / ITemplate (dialogs, popups)","  → rendered once at the end of <body>"],fill="#f3e5f5",stroke=C["json"])
arrow(MX+360,165,SX,165,C["json"],dash=True)
arrow(MX+360,280,SX,280,C["json"],dash=True)

# render
arrow(MX+180,320,MX+180,365)
box(MX,365,360,80,"render()",["render_enabled = false → nothing","CacheFactory::CacheEntryOutput(this)","  cached & fresh → echo, return"],fill=C["cache"],stroke=C["caches"])
arrow(MX+180,445,MX+180,465)
box(MX,465,360,100,"startRender()",["finalize() → syncAttrs()","  last moment to map state to attributes","echo <tag class attrs style>","renderCaption()"])
arrow(MX+180,565,MX+180,585)
box(MX,585,360,100,"renderImpl()",["Component: echo buffer (tr() if translation_enabled)","Container: items then buffer (or items_first)","  each child → render()  — recursion","  wrapper_enabled = false → children only"])
arrow(MX+180,685,MX+180,705)
box(MX,705,360,80,"finishRender()",["echo </tag>  (closingTagRequired)","exception → message echoed, not cached","otherwise output stored to PageCache"])
box(60,365,310,190,"PageCache",["only when PAGE_CACHE_ENABLED and","  setCacheable(true)","key: script + class + name","  + iterator SQL hash (result views)","TTL: PAGE_CACHE_TTL","the whole subtree is replayed —","  children are not rendered at all"],fill=C["cache"],stroke=C["caches"])
arrow(370,405,MX,405,C["caches"],dash=True)
box(60,585,310,200,"Override points",["syncAttrs() — attributes from state","finalize() — structure changes","startRender() / finishRender()","  — wrapping markup","renderImpl() — the content","requiredStyle() / requiredScript()","getCacheName() — cache identity"],fill="#ffffff")
arrow(370,640,MX,640,dash=True)

# result view
arrow(MX+180,785,MX+180,835)
box(MX,835,360,90,"AbstractResultView::startRender()",["processIterator() (once)","total_rows = 0 → list_empty message","  viewport / header / footer hidden"])
arrow(MX+180,925,MX+180,945)
box(MX,945,360,142,"paginate()",["iterator->count()","  SELECT COUNT(*) FROM (stmt, column '1', no order)","paginator->applyOrder(stmt, default_order)","  ?orderby= & orderdir= from the request","paginator->calculate(total, items_per_page)","paginator->applyLimit(stmt) — ?page="],fill=C["db"],stroke=C["dbs"])
arrow(MX+180,1087,MX+180,1105)
box(MX,1105,360,100,"iterator->exec()",["SelectQuery → DBQuery → DBManager::Driver()","execLateLookup → stmt->lateLookup(table, key)","unbuffered: rows stream while rendering","  driver #1 stays busy until the last row"],fill=C["db"],stroke=C["dbs"])
box(60,835,310,200,"IDataIterator",["exec() · next() · nextResult() · count()","key() — primary key name","SelectQuery — SQLSelect on a DB driver","ArrayDataIterator — PHP array","  (DBEnumIterator: enum values)","used by result views AND form fields","  (DataIteratorField)"],fill=C["db"],stroke=C["dbs"])
arrow(370,1000,MX,1000,C["dbs"],dash=True)
box(SX,835,430,160,"Paginator",["keys: orderby · orderdir · page · view","addOrderColumn(OrderColumn::Labeled(...))","ResultViewHeader / Footer render the","  sort and page controls","AppendHeadLinks(view, page):","  rel=prev / rel=next + page number in title","  (paginate() runs early, from the page head)"])
arrow(MX+360,1010,SX,950,dash=True)
box(SX,1015,430,190,"View types",["ItemView — one DataIteratorItem per row","  (ListItem, GalleryViewItem, TextItem,","   store ProductListItem ...)","TableView — TableColumn per field","  header cells once, then one cell per","  column per row (TableCell, ImageCell,","  StorageItemCell, ActionsCell ...)"])

# rows
arrow(MX+180,1205,MX+180,1255)
box(MX,1255,360,110,"renderItems()",["while (row = iterator->nextResult())","  renderItem(RawResult row)","  position_index++","stream ends → result freed, driver #1 free"])
arrow(MX+180,1365,MX+180,1385)
box(MX,1385,360,150,"ItemView::renderItem(row)",["ONE item renderer instance for all rows:","  setPosition(index) · parity attribute","  setDataID(row[key])","  setData(row) — resets the per-row state","  render()","→ the item's own Component::render()"])
box(60,1255,310,280,"Flyweight item renderer",["the same DataIteratorItem object","  renders every row","everything row specific must be","  reset in setData(row):","  value  ← row[value_key]","  label  ← row[label_key]","  data_attributes ← row fields","state left from the previous row","  leaks into the next one"],fill="#fdecea",stroke=C["accent"])
arrow(370,1460,MX,1460,C["accent"],dash=True)

# inside an item
arrow(MX+180,1535,MX+180,1585)
box(MX,1585,360,150,"DataIteratorItem::setData(row)",["value / label from the configured keys","data attributes → HTML attributes","Action / Link URLs:","  DataParameter('editID', 'prodID')","  → ?editID=<row prodID>","child components get their part of the row"])
arrow(MX+180,1735,MX+180,1755)
box(MX,1755,360,110,"Typical item contents",["StorageItem → image / file URLs","  (versioned, slug, see request diagram)","tr() texts → translation lookup","  on a second DB connection (#2)"])
box(60,1585,310,280-0,"Connections while rendering",["#1 — the list iterator, busy until","     its last row is read","#2 — tr(), small lookups inside items","DBManager::Driver() hands out the","  first idle driver (max 4)","a query inside an item that reads","  everything frees #2 again","nested result views need their","  own connection each"],fill=C["db"],stroke=C["dbs"])
arrow(370,1810,MX,1810,C["dbs"],dash=True)
box(SX,1585,430,170,"Writing a renderer",["extend DataIteratorItem (or ListItem)","constructor: build child components once","setData(row): copy the row into them","renderImpl(): render the children","no queries per row unless unavoidable —","  join the data into the iterator instead"])

# legend
LY=1915
t(24,LY,"Legend",16,"bold")
items=[(C["line"],False,"main flow"),(C["dbs"],True,"database"),(C["caches"],True,"page cache"),(C["json"],True,"events"),(C["accent"],True,"pitfall")]
for i,(c,d,l) in enumerate(items):
    x=24+i*300
    arrow(x,LY+30,x+60,LY+30,c,dash=d); t(x+72,LY+35,l,13,fill=C["muted"])
t(24,LY+80,"Component · Container · AbstractResultView · ItemView · TableView · DataIteratorItem · Paginator — components/ and iterators/ in sparkbox/lib",13,fill=C["muted"])
t(W-24,LY+80,"generated for the SparkBox README — edit freely in Inkscape",12,fill=C["muted"],anchor="end",italic=True)

defs="".join(f'<marker id="m{k}" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" fill="{v}"/></marker>' for k,v in dict(a=C["line"],r=C["accent"],p=C["json"],b=C["caches"],y=C["dbs"],g=C["adm"]).items())
svg=f'<?xml version="1.0" encoding="UTF-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">\n<defs>{defs}</defs>\n<rect width="{W}" height="{H}" fill="#ffffff"/>\n'+"\n".join(out)+"\n</svg>\n"
open("anatomy-of-rendering.svg","w").write(svg)
