// node_modules/js-loading-overlay/dist/js-loading-overlay.min.js
!function(e) {
    var t = {};
    function n(o) {
        if (t[o])
            return t[o].exports;
        var i = t[o] = {
            i: o,
            l: false,
            exports: {}
        };
        return e[o].call(i.exports, i, i.exports, n),
        i.l = true,
        i.exports;
    }
    n.m = e,
    n.c = t,
    n.d = function(e2, t2, o) {
        n.o(e2, t2) || Object.defineProperty(e2, t2, {
            enumerable: true,
            get: o
        });
    }
    ,
    n.r = function(e2) {
        "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(e2, Symbol.toStringTag, {
            value: "Module"
        }),
        Object.defineProperty(e2, "__esModule", {
            value: true
        });
    }
    ,
    n.t = function(e2, t2) {
        if (1 & t2 && (e2 = n(e2)),
        8 & t2)
            return e2;
        if (4 & t2 && "object" == typeof e2 && e2 && e2.__esModule)
            return e2;
        var o = /* @__PURE__ */
        Object.create(null);
        if (n.r(o),
        Object.defineProperty(o, "default", {
            enumerable: true,
            value: e2
        }),
        2 & t2 && "string" != typeof e2)
            for (var i in e2)
                n.d(o, i, function(t3) {
                    return e2[t3];
                }
                .bind(null, i));
        return o;
    }
    ,
    n.n = function(e2) {
        var t2 = e2 && e2.__esModule ? function() {
            return e2.default;
        }
        : function() {
            return e2;
        }
        ;
        return n.d(t2, "a", t2),
        t2;
    }
    ,
    n.o = function(e2, t2) {
        return Object.prototype.hasOwnProperty.call(e2, t2);
    }
    ,
    n.p = "",
    n(n.s = 0);
}([function(e, t, n) {
    e.exports = n(1);
}
, function(e, t) {
    function n(e2, t2) {
        for (var n2 = 0; n2 < t2.length; n2++) {
            var o2 = t2[n2];
            o2.enumerable = o2.enumerable || false,
            o2.configurable = true,
            "value"in o2 && (o2.writable = true),
            Object.defineProperty(e2, o2.key, o2);
        }
    }
    var o = function() {
        function e2() {
            !function(e3, t3) {
                if (!(e3 instanceof t3))
                    throw new TypeError("Cannot call a class as a function");
            }(this, e2),
            this.options = {
                overlayBackgroundColor: "#666666",
                overlayOpacity: 0.6,
                spinnerIcon: "ball-circus",
                spinnerColor: "#000",
                spinnerSize: "3x",
                overlayIDName: "overlay",
                spinnerIDName: "spinner",
                offsetY: 0,
                offsetX: 0,
                lockScroll: false,
                containerID: null,
                spinnerZIndex: 99999,
                overlayZIndex: 99998
            },
            this.stylesheetBaseURL = "./assets/js-loading-overlay/css/",
            this.spinner = null,
            this.spinnerStylesheetURL = null,
            this.numberOfEmptyDivForSpinner = {
                "ball-8bits": 16,
                "ball-atom": 4,
                "ball-beat": 3,
                "ball-circus": 5,
                "ball-climbing-dot": 1,
                "ball-clip-rotate": 1,
                "ball-clip-rotate-multiple": 2,
                "ball-clip-rotate-pulse": 2,
                "ball-elastic-dots": 5,
                "ball-fall": 3,
                "ball-fussion": 4,
                "ball-grid-beat": 9,
                "ball-grid-pulse": 9,
                "ball-newton-cradle": 4,
                "ball-pulse": 3,
                "ball-pulse-rise": 5,
                "ball-pulse-sync": 3,
                "ball-rotate": 1,
                "ball-running-dots": 5,
                "ball-scale": 1,
                "ball-scale-multiple": 3,
                "ball-scale-pulse": 2,
                "ball-scale-ripple": 1,
                "ball-scale-ripple-multiple": 3,
                "ball-spin": 8,
                "ball-spin-clockwise": 8,
                "ball-spin-clockwise-fade": 8,
                "ball-spin-clockwise-fade-rotating": 8,
                "ball-spin-fade": 8,
                "ball-spin-fade-rotating": 8,
                "ball-spin-rotate": 2,
                "ball-square-clockwise-spin": 8,
                "ball-square-spin": 8,
                "ball-triangle-path": 3,
                "ball-zig-zag": 2,
                "ball-zig-zag-deflect": 2,
                cog: 1,
                "cube-transition": 2,
                fire: 3,
                "line-scale": 5,
                "line-scale-party": 5,
                "line-scale-pulse-out": 5,
                "line-scale-pulse-out-rapid": 5,
                "line-spin-clockwise-fade": 8,
                "line-spin-clockwise-fade-rotating": 8,
                "line-spin-fade": 8,
                "line-spin-fade-rotating": 8,
                pacman: 6,
                "square-jelly-box": 2,
                "square-loader": 1,
                "square-spin": 1,
                timer: 1,
                "triangle-skew-spin": 1
            };
        }
        var t2, o2, i;
        return t2 = e2,
        (o2 = [{
            key: "show",
            value: function(e3) {
                this.setOptions(e3),
                this.addSpinnerStylesheet(),
                this.generateSpinnerElement(),
                this.options.lockScroll && (document.body.style.overflow = "hidden",
                document.documentElement.style.overflow = "hidden"),
                this.generateAndAddOverlayElement();
            }
        }, {
            key: "hide",
            value: function() {
                this.options.lockScroll && (document.body.style.overflow = "",
                document.documentElement.style.overflow = "");
                var e3 = document.getElementById("loading-overlay-stylesheet");
                e3 && (e3.disabled = true,
                e3.parentNode.removeChild(e3),
                document.getElementById(this.options.overlayIDName).remove(),
                document.getElementById(this.options.spinnerIDName).remove());
            }
        }, {
            key: "setOptions",
            value: function(e3) {
                if (void 0 !== e3)
                    for (var t3 in e3)
                        this.options[t3] = e3[t3];
            }
        }, {
            key: "generateAndAddOverlayElement",
            value: function() {
                var e3 = "50%";
                0 !== this.options.offsetX && (e3 = "calc(50% + " + this.options.offsetX + ")");
                var t3 = "50%";
                if (0 !== this.options.offsetY && (t3 = "calc(50% + " + this.options.offsetY + ")"),
                this.options.containerID && document.body.contains(document.getElementById(this.options.containerID))) {
                    var n2 = '<div id="'.concat(this.options.overlayIDName, '" style="display: block !important; position: absolute; top: 0; left: 0; overflow: auto; opacity: ').concat(this.options.overlayOpacity, "; background: ").concat(this.options.overlayBackgroundColor, '; z-index: 50; width: 100%; height: 100%;"></div><div id="').concat(this.options.spinnerIDName, '" style="display: block !important; position: absolute; top: ').concat(t3, "; left: ").concat(e3, '; -webkit-transform: translate(-50%); -ms-transform: translate(-50%); transform: translate(-50%); z-index: 9999;">').concat(this.spinner, "</div>")
                      , o3 = document.getElementById(this.options.containerID);
                    return o3.style.position = "relative",
                    void o3.insertAdjacentHTML("beforeend", n2);
                }
                var i2 = '<div id="'.concat(this.options.overlayIDName, '" style="display: block !important; position: fixed; top: 0; left: 0; overflow: auto; opacity: ').concat(this.options.overlayOpacity, "; background: ").concat(this.options.overlayBackgroundColor, "; z-index: ").concat(this.options.overlayZIndex, '; width: 100%; height: 100%;"></div><div id="').concat(this.options.spinnerIDName, '" style="display: block !important; position: fixed; top: ').concat(t3, "; left: ").concat(e3, "; -webkit-transform: translate(-50%); -ms-transform: translate(-50%); transform: translate(-50%); z-index: ").concat(this.options.spinnerZIndex, ';">').concat(this.spinner, "</div>");
                document.body.insertAdjacentHTML("beforeend", i2);
            }
        }, {
            key: "generateSpinnerElement",
            value: function() {
                var e3 = this
                  , t3 = Object.keys(this.numberOfEmptyDivForSpinner).find(function(t4) {
                    return t4 === e3.options.spinnerIcon;
                })
                  , n2 = this.generateEmptyDivElement(this.numberOfEmptyDivForSpinner[t3]);
                this.spinner = '<div style="color: '.concat(this.options.spinnerColor, '" class="la-').concat(this.options.spinnerIcon, " la-").concat(this.options.spinnerSize, '">').concat(n2, "</div>");
            }
        }, {
            key: "addSpinnerStylesheet",
            value: function() {
                this.setSpinnerStylesheetURL();
                var e3 = document.createElement("link");
                e3.setAttribute("id", "loading-overlay-stylesheet"),
                e3.setAttribute("rel", "stylesheet"),
                e3.setAttribute("type", "text/css"),
                e3.setAttribute("href", this.spinnerStylesheetURL),
                document.getElementsByTagName("head")[0].appendChild(e3);
            }
        }, {
            key: "setSpinnerStylesheetURL",
            value: function() {
                this.spinnerStylesheetURL = this.stylesheetBaseURL + this.options.spinnerIcon + ".min.css";
            }
        }, {
            key: "generateEmptyDivElement",
            value: function(e3) {
                for (var t3 = "", n2 = 1; n2 <= e3; n2++)
                    t3 += "<div></div>";
                return t3;
            }
        }]) && n(t2.prototype, o2),
        i && n(t2, i),
        e2;
    }();
    window.JsLoadingOverlay = new o(),
    e.exports = JsLoadingOverlay;
}
]);
