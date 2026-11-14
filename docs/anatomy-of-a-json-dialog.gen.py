# Generates anatomy-of-a-json-dialog.svg (plain SVG, Inkscape friendly: no CSS vars, real <text>)
from xml.sax.saxutils import escape as esc
W,H=1600,1900
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
band(90,330,C["boot"],"Page render (server)",1)
band(420,300,C["page"],"Page load (browser)",2)
band(720,520,C["render"],"render — show()",3)
band(1240,500,"#e3f0e6","submit — Submit button",4)

t(24,44,"SparkBox — anatomy of a JSON form dialog (JSONFormDialog)",28,"bold")
t(24,72,"A dialog is an HTML <template> rendered once with the page, cloned by JavaScript, and filled through JSON requests to a responder — render first, submit after.",14,fill=C["muted"])

JX,SX=80,860   # browser column, server column
t(JX+300,112,"Browser — sparkfront/js",15,"bold",anchor="middle")
t(SX+340,112,"Server — sparkbox/lib",15,"bold",anchor="middle")

# 1 page render (server)
box(SX,125,680,92,"new JSONFormDialog()   (dialogs/json/JSONFormDialog.php)",["MessageDialog → ConfirmMessageDialog → JSONDialog → JSONFormDialog","Container: .Dialog > .Inner > .Header .Title · .Content .Text · .Footer .Buttons","implements IPageComponent + ITemplate (templateID() = 'JSONFormDialog')"])
arrow(SX+340,217,SX+340,237)
box(SX,237,330,170,"HTMLBody (COMPONENT_CREATED)",["IPageComponent → rendered at the end","  of <body>, once per class+name","ITemplate → wrapped in HTMLTemplate:","  <template id='JSONFormDialog'>","    <div class='JSONFormDialog Dialog'","      responder='…' type='Plain'>"],fill="#ffffff")
box(SX+350,237,330,170,"HTMLHead",["requiredScript():","  MessageDialog.js · JSONDialog.js","  JSONFormDialog.js (+ TemplateComponent,","  TemplateFactory, JSONRequest from SparkPage)","requiredStyle(): JSONFormDialog.css","local files get ?v=<file time>"],fill="#ffffff")
box(JX,237,600,110,"Page scripts (InlineScript, IPageComponent)",["define the opener, e.g. showSectionChooserForm(prodID):","  let d = new JSONFormDialog(); d.setTitle(…);","  d.setResponder('SectionChooserFormResponder');","  d.getJSONRequest().setParameter('prodID', prodID); d.show();"])
arrow(SX,300,JX+600,300,C["adm"],dash=True); t(JX+612,288,"HTML page:",12,fill=C["adm"]); t(JX+612,318,"<template>, JS, CSS",12,fill=C["adm"])

# 2 page load (browser)
box(JX,440,600,74,"document.templateFactory = new TemplateFactory()   (TemplateFactory.js)",["getElement(id): template#id → content.firstChild","nodeList(html) / appendNodes(el, nodes) / appendContent(el, html)"])
arrow(JX+300,514,JX+300,534)
box(JX,534,600,170,"new JSONFormDialog()  — JS class chain",["SparkObject  observers · notify()","  Component  selector from class / name / field / id","    TemplateComponent  clones template#<class name> (constructor.name)","      MessageDialog  .Header .Content .Footer · show() / remove() · index","        JSONDialog  new JSONRequest(), responder from the element attribute","          JSONFormDialog  render / submit / loader"],fill="#ffffff")
box(SX,440,680,150,"The responder must exist for the request",["created by the page or its content (constructor of a template content,","  form, list …) → RequestController::Add(name)","or early: RequestResponder::EarlyProcessing() = true — Module creates it","  by name before the page (help, uploads, translators, section chooser …)","JSON requests go to the current page URL (window.location) — the same page","  that showed the dialog builds the same responders again"],fill=C["db"],stroke=C["dbs"])

# 3 render
box(JX,740,600,96,"show()",["MessageDialog.show(): index++, append to <body>, modal","JSONFormDialog: request.setFunction('render')","  onSuccess = processRenderResult; request.start()","EVENT_STARTED → content = loader (spinner), 'loading' attribute"])
arrow(JX+600,800,SX,800,C["json"]); t(JX+612,788,"GET function=render",12,fill=C["json"]); t(JX+612,818,"responder=X + params",12,fill=C["json"])
box(SX,760,680,100,"RequestController::Process()",["Module::Response(): after authorization — early responder, or after page create","classic pages: SparkPage::startRender()","isJSONRequest → Get(responder name) → accept(): parse parameters (prodID …)","  → processImpl()"])
arrow(SX+340,860,SX+340,880)
box(SX,880,680,160,"JSONResponder::processImpl()",["new JSONResponse(name) · ob_start(callback)","calls '_' . function  →  _render(JSONResponse)","JSONFormResponder::_render(): createForm() in the constructor,","  FormRenderer without submit line → render() echoes the FORM","contents = output buffer · status OK → json_encode → exit","fatal error → the buffer callback returns {status: Error, message}"],fill="#f3e5f5",stroke=C["json"])
arrow(SX,960,JX+600,960,C["json"]); t(JX+612,948,"JSON: status,",12,fill=C["json"]); t(JX+612,978,"message, contents",12,fill=C["json"])
box(JX,880,600,150,"JSONRequest.onLoad()",["HTTP status ≠ 200 or status ≠ 'OK' → JSONRequestError","  → onError → showAlert(description)","JSON.parse → JSONRequestResult {status, response}","onSuccess(result) → processRenderResult","EVENT_FINISHED → 'loading' removed"],fill="#ffffff")
arrow(JX+300,1030,JX+300,1050)
box(JX,1050,600,150,"loadContent(response.contents)",["content.innerHTML = ''","templateFactory.appendContent(content, html):","  nodeList(): <template>.innerHTML = html → nodes","    inline <script> re-created (innerHTML does not run them)","  appendNodes() → SparkEvent DOM_UPDATED on document","    components listening initialise the new inputs"],fill=C["cache"],stroke=C["caches"])

# 4 submit
box(JX,1270,600,110,"buttonAction('confirm') → submitForm()",["form = element FORM; name = form name","request.setFunction('submit')","post SubmitForm = <form name> + new FormData(form)","onSuccess = processSubmitResult(result, name)"])
arrow(JX+600,1330,SX,1330,C["json"]); t(JX+612,1318,"POST function=submit",12,fill=C["json"]); t(JX+612,1348,"FormData",12,fill=C["json"])
box(SX,1270,680,180,"JSONFormResponder::_submit()",["FormProcessor::process(form): SubmitForm = form name?","  loadPostData → validate → processImpl","STATUS_OK → onProcessSuccess(resp)  (subclass: store, mail …)","  resp->message, resp->gtm (conversion event) — no contents","STATUS_ERROR → onProcessError(resp)","  resp->message + render the form again with its errors → contents"],fill="#f3e5f5",stroke=C["json"])
arrow(SX,1400,JX+600,1400,C["json"]); t(JX+612,1388,"JSON: message,",12,fill=C["json"]); t(JX+612,1418,"contents?, gtm?",12,fill=C["json"])
box(JX,1420,600,130,"processSubmitResult()",["contents → loadContent(contents) + showAlert(message)","  (the form with its errors, same dialog)","no contents → GTM event (SparkEvent GTM_EVENT) if gtm,","  remove() the dialog, showAlert(message)","cancel → remove()"],fill="#ffffff")
box(SX,1475,680,250,"Examples",["admin products list: SectionChooserFormResponder, ImportUpdateFormResponder","storefront product: QueryProductFormResponder, NotifyInstockFormResponder,","  OrderProductFormResponder (variant[] post parameters)","contacts: ContactRequestFormResponder · cart: VoucherFormResponder","","Same request path without a dialog:","  JSONComponent.js → responder 'render' → one root node appended","    (JSONComponentResponder renders a server Component)","  JSONItemView, TemplateHelp, MCEImageBrowserDialog, translation dialogs"],fill="#ffffff")
box(JX,1575,600,150,"Writing a dialog",["PHP: class X extends JSONFormResponder { createForm(); onProcessSuccess() }","  created by the page — or EarlyProcessing() true if it needs nothing","  from the page","JS: new JSONFormDialog(); setResponder('X'); setTitle(); show()","  the template is on the page once any JSONFormDialog is created","  (StorePageBase / admin page create one for this)"])

# legend
LY=1770
t(24,LY,"Legend",16,"bold")
items=[(C["line"],False,"main flow"),(C["json"],False,"JSON request / response"),(C["adm"],True,"page HTML"),(C["dbs"],True,"responder lifetime"),(C["caches"],True,"DOM update")]
for i,(c,d,l) in enumerate(items):
    x=24+i*300
    arrow(x,LY+30,x+60,LY+30,c,dash=d); t(x+72,LY+35,l,13,fill=C["muted"])
t(24,LY+80,"JS: sparkfront/js (SparkObject, Component, TemplateComponent, TemplateFactory, JSONRequest, dialogs/) · PHP: dialogs/, responders/json/, pages/HTMLBody.php",13,fill=C["muted"])
t(W-24,LY+80,"generated for the SparkBox README — edit freely in Inkscape",12,fill=C["muted"],anchor="end",italic=True)

defs="".join(f'<marker id="m{k}" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" fill="{v}"/></marker>' for k,v in dict(a=C["line"],r=C["accent"],p=C["json"],b=C["caches"],y=C["dbs"],g=C["adm"]).items())
svg=f'<?xml version="1.0" encoding="UTF-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">\n<defs>{defs}</defs>\n<rect width="{W}" height="{H}" fill="#ffffff"/>\n'+"\n".join(out)+"\n</svg>\n"
open("anatomy-of-a-json-dialog.svg","w").write(svg)
