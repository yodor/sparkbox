function AuthForm() {
    this.component_class = "FORM.AuthFormRenderer";
    this.cls = this.component_class;
}

AuthForm.prototype.attachWith = function (name) {
    if (name) {
        this.name = name;
        this.cls = this.component_class + "[name='" + name + "']";

    }

    try {

        this.form = $(this.cls);
        if (!this.form.get(0)) throw "Required form component not found";

        this.username = this.form.find("INPUT[name='username']").first();
        if (!this.username.get(0)) throw "Required field not found";

        this.password = this.form.find("INPUT[name='password']").first();
        if (!this.password.get(0)) throw "Required field not found";

        this.rand = this.form.find("INPUT[type='hidden'][name='rand']").first();
        if (!this.rand.get(0)) throw "Required field not found";

        this.pass = this.form.find("INPUT[type='hidden'][name='pass']").first();
        if (!this.pass.get(0)) throw "Required field not found";

        this.form.on("submit", function (event) {

            return this.onSubmit(event);

        }.bind(this));
    } catch (exx) {
        alert("Unable to attach with requested AuthForm: " + exx);
    }

}
AuthForm.prototype.onSubmit = function (event) {
    this.username.val(trim(this.username.val()));
    this.password.val(trim(this.password.val()));

    let u = this.username.val();
    let p = this.password.val();
    let r = this.rand.val();

    try {
        if (u.length < 1) {
            throw "Input your email";
        }
        this.processEmail();

        if (!(typeof hex_hmac_md5 === 'function')) throw "Required hashing function not defined";

        this.processPassword();

    } catch (exx) {

        showAlert(exx);
        return false;

    }


    return true;

}
AuthForm.prototype.processPassword = function () {

    let p = this.password.val();
    let r = this.rand.val();

    if (p.length < 1) throw "Input your password to continue.";
    if (p.length < 6) throw "Minimum password of 6 symbols required.";

    this.password.val("");
    this.pass.val(hex_hmac_md5(hex_md5(p), r));
    this.rand.val("");

}


AuthForm.prototype.processEmail = function () {
    let u = this.username.val();

    let emailFilter = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    if (!emailFilter.test(u)) {              //test email for illegal 
        throw "Please enter a valid email address.";
    }

}