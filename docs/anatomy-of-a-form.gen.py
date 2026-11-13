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
band(90,140,"#f5f7fa","Entry",0)
band(230,250,C["boot"],"Setup",1)
band(480,330,C["page"],"Load — processInput()",2)
band(810,330,"#e3f0e6","POST — FormProcessor",3)
band(1140,420,C["db"],"Transaction — BeanTransactor",4)
band(1560,410,C["render"],"Render — FormRenderer",5)

t(24,44,"SparkBox — anatomy of a form (BeanFormEditor)",28,"bold")
t(24,72,"The same editor handles both requests: GET loads and renders, POST loads, validates, transacts and redirects (Post/Redirect/Get).",14,fill=C["muted"])

MX=420
box(MX,110,360,80,"Browser request",["GET  edit.php?editID=12   (editID < 1 = add mode)","POST SubmitForm=<form name> + input values"])
arrow(MX+180,190,MX+180,250)

# setup
box(MX,250,360,100,"InputForm",["InputGroup(s) of DataInput","DataInputFactory::Create(type, name, label, required)","  DataInput + InputProcessor + EmptyValueValidator","  + InputField renderer (TextField, SelectField ...)"])
box(MX,370,360,94,"new BeanFormEditor(bean, form)",["extends FormRenderer (the form component)","FormProcessor · BeanTransactor(bean)","event EDITOR_CREATED"])
arrow(MX+180,350,MX+180,370)
box(60,250,310,214,"DataInput",["name · value · error · editable","","InputProcessor","  loadBeanData / loadPostData","  transactValue / beforeCommit / afterCommit","IInputValidator — validate()","InputField renderer — render()","  DataIteratorField: IDataIterator + item"],fill="#ffffff")
arrow(370,300,MX,300,dash=True)

# load
arrow(MX+180,464,MX+180,505)
box(MX,505,360,62,"editID",["setEditID() or $_GET['editID'] → form + transactor"])
arrow(MX+180,567,MX+180,590)
box(MX,590,360,128,"InputForm::loadBeanData(editID, bean)",["edit mode only (editID > 0)","bean->getByID(editID) → row","each InputProcessor::loadBeanData(editID, bean, row)","  → DataInput::setValue(row[name])","event FORM_BEAN_LOADED"],fill=C["db"],stroke=C["dbs"])
arrow(MX+180,718,MX+180,738)
box(MX,738,360,54,"FormProcessor::process(form)",["$_REQUEST['SubmitForm'] == form name ?"])
poly([(MX+360,765),(MX+400,765),(MX+400,1580),(MX+360,1580)],C["accent"],dash=True,label="no → NOT_PROCESSED → render",lx=MX+408,ly=1240)
box(60,590,310,150,"Transact bean",["InputProcessor::setTransactBean(bean)","values live in another table that","references the main key (photos,","attributes, sections ...)","loadTransactBean(): rows by foreign key","written in beforeCommit()"],fill=C["db"],stroke=C["dbs"])
arrow(370,665,MX,665,C["dbs"],dash=True)

# post
arrow(MX+180,792,MX+180,835)
box(MX,835,360,80,"InputForm::loadPostData($_REQUEST)",["each editable InputProcessor::loadPostData()","  exception → DataInput::setError()"])
arrow(MX+180,915,MX+180,935)
box(MX,935,360,62,"InputForm::validate()",["each editable DataInput::validate() → IInputValidator"])
arrow(MX+180,997,MX+180,1017)
box(MX,1017,360,100,"processImpl()",["errors → STATUS_ERROR","  'Please make input in all required fields'","else STATUS_OK","event FORM_PROCESSED"])
poly([(MX+360,1050),(MX+380,1050),(MX+380,1600),(MX+360,1600)],C["accent"],dash=True)
t(MX+388,1100,"error → render",12,fill=C["accent"])
t(MX+388,1115,"with input errors",12,fill=C["accent"])
box(60,835,310,140,"Session uploads",["SessionUploadInput / SessionImage","files are uploaded before submit","(ajax, chunked) and kept in the session","the POST carries only their UIDs","  uid_<name>[] → loadPostData()"],fill=C["cache"],stroke=C["caches"])
arrow(370,875,MX,875,C["caches"],dash=True)

# transaction
arrow(MX+180,1117,MX+180,1165)
box(MX,1165,360,80,"BeanTransactor::processForm(form)",["each InputProcessor::transactValue(transactor)","  → appendValue(column, value)","event FORM_VALUES_TRANSACTED"])
arrow(MX+180,1245,MX+180,1265)
box(MX,1265,360,180,"BeanTransactor::processBean()",["DBDriver::transaction()","processBeanTransaction():","  editID > 0 → bean->update(editID, values, $db)","  else → bean->insert(values, $db) → lastID","beforeCommit(): transact bean inputs","event BEFORE_COMMIT → commit()","afterCommit() (errors only logged) · AFTER_COMMIT","event FORM_BEAN_TRANSACED"],fill=C["db"],stroke=C["dbs"])
arrow(MX+180,1445,MX+180,1475)
box(MX,1475,360,70,"Redirect",["Session alert 'Information was added / updated'","URL::Current() + editID=lastID → Location"])
poly([(MX+360,1510),(MX+440,1510),(MX+440,100),(MX+300,100),(MX+300,110)],C["accent"],label="302 → new GET (edit mode)",lx=MX+448,ly=1505)
box(60,1265,310,180,"On error",["exception anywhere in the transaction","  → rollback() (nested = savepoint)","  → lastID = -1","BeanFormEditor catches it:","  Session alert + editor error","  → render with the posted values"],fill="#fdecea",stroke=C["accent"])
arrow(370,1355,MX,1355,C["accent"],dash=True)
box(SX:=1135,1165,430,150,"After commit (DBDriver::onCommit)",["DBTableBean::manageCache() for every written row:","  StorageVersion marker → new image URLs","  CacheFactory::DeleteBeanCache()","nothing happens for rolled back writes"],fill=C["cache"],stroke=C["caches"])
arrow(MX+360,1330,SX,1260,C["caches"],dash=True)

# render
box(MX,1580,360,180,"FormRenderer::renderImpl()",["renderInputs(): per InputGroup","  InputGroupRenderer (or plain for one group)","  InputComponent per DataInput","    InputLabel + InputField + error","renderSubmitLine():","  button name=SubmitForm value=<form name>","  — what FormProcessor matches on POST"])
arrow(MX+180,1760,MX+180,1790)
box(MX,1790,360,150,"InputField renderers",["TextField · TextArea · MCETextArea · DateField","SessionImage / SessionFile (upload widgets)","DataIteratorField: SelectField, RadioField,","  CheckField, NestedSelectField","    iterator->exec() → DataIteratorItem per row","    selected when the row value = DataInput value"])
box(60,1580,310,180,"Rendering a value",["DataInput holds the value either from","  loadBeanData (GET, edit)","  or loadPostData (POST with errors)","so a failed POST re-renders exactly","what the user typed, with the errors","set by the processors and validators"],fill="#ffffff")
arrow(370,1670,MX,1670,dash=True)
box(SX,1580,430,180,"Events (SparkEventManager)",["BeanFormEditorEvent:","  EDITOR_CREATED · FORM_BEAN_LOADED","  FORM_PROCESSED · FORM_VALUES_TRANSACTED","  FORM_BEAN_TRANSACED","BeanTransactorEvent:","  BEFORE_COMMIT · AFTER_COMMIT (with $db)"],fill="#f3e5f5",stroke=C["json"])
box(SX,1790,430,150,"Customising",["IFormProcessor — replace FormProcessor","  (setProcessor) for custom checks","InputProcessor subclasses per input type","BeanTransactor values: appendValue /","  assignInsertValue / assignUpdateValue","setRedirectURL() — redirect elsewhere"])

# legend
LY=1995
t(24,LY,"Legend",16,"bold")
items=[(C["line"],False,"main flow"),(C["accent"],False,"exit / redirect / error"),(C["dbs"],True,"database"),(C["caches"],True,"session / cache"),(C["json"],True,"events")]
for i,(c,d,l) in enumerate(items):
    x=24+i*300
    arrow(x,LY+30,x+60,LY+30,c,dash=d); t(x+72,LY+35,l,13,fill=C["muted"])
t(24,LY+80,"InputForm · DataInput · InputProcessor · IInputValidator · InputField — forms/ and input/ in sparkbox/lib",13,fill=C["muted"])
t(W-24,LY+80,"generated for the SparkBox README — edit freely in Inkscape",12,fill=C["muted"],anchor="end",italic=True)

defs="".join(f'<marker id="m{k}" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" fill="{v}"/></marker>' for k,v in dict(a=C["line"],r=C["accent"],p=C["json"],b=C["caches"],y=C["dbs"],g=C["adm"]).items())
svg=f'<?xml version="1.0" encoding="UTF-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">\n<defs>{defs}</defs>\n<rect width="{W}" height="{H}" fill="#ffffff"/>\n'+"\n".join(out)+"\n</svg>\n"
open("anatomy-of-a-form.svg","w").write(svg)
