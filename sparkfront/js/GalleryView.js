function choosePosition(key, val)
{
    console.log(key);
    console.log(val);

    console.log(document.location.href);

    let pos = window.prompt("Position","");
    if (pos>0) {
        document.location.href = document.location.href + "&cmd=reposition&type=fixed&position=" + pos + "&item_id="+key;
    }
}