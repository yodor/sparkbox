function LoginForm() {
    this.component_class = "FORM";
    this.cls = this.component_class;
}

LoginForm.prototype.attachWith = function (name) {
    if (name) {
        this.name = name;
        this.cls = this.component_class + "[name='" + name + "']";

    }

    try {

        this.form = $(this.cls);
        if (!this.form.get(0)) throw "Required form component class not found";

        this.email = this.form.find("INPUT[name='email']").first();
        if (!this.email.get(0)) throw "Required field not found";

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
LoginForm.prototype.onSubmit = function (event) {
    this.email.val(trim(this.email.val()));
    this.password.val(trim(this.password.val()));

    let u = this.email.val();
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
LoginForm.prototype.processPassword = function () {

    let p = this.password.val();
    let r = this.rand.val();

    if (p.length < 1) throw "Input your password to continue.";
    if (p.length < 6) throw "Minimum password of 6 symbols required.";

    this.password.val("");
    this.pass.val(hex_hmac_md5(hex_md5(p), r));
    this.rand.val("");

}


LoginForm.prototype.processEmail = function () {
    let u = this.email.val();

    let emailFilter = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    if (!emailFilter.test(u)) {              //test email for illegal 
        throw "Please enter a valid email address.";
    }

}