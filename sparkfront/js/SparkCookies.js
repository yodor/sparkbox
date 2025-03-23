class SparkCookies extends SparkObject
{
    constructor() {
        super();
    }

    accept() {
        Cookies.set('accept_cookies', 1, { expires: 3650 });
        this.checkAccepted();
        this.updateCookiesPanel();
    }

    isAccepted() {
        if (Cookies.get('accept_cookies')>0) return true;
        return false;
    }

    checkAccepted() {

    }

    updateCookiesPanel()
    {
        let isAccepted = this.isAccepted();
        document.querySelector(".section.cookies").setAttribute("accepted", isAccepted);
    }
}


