class SparkCookies extends SparkObject
{
    constructor() {
        super();
    }

    accept() {
        Cookies.set('accept_cookies', 1, { expires: 3650 });
        this.checkAccepted();
    }

    isAccepted() {
        return Cookies.get('accept_cookies');
    }

    checkAccepted() {

    }

}


