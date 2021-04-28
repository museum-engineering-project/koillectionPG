(self.webpackChunkkoillection=self.webpackChunkkoillection||[]).push([[18],{74018:(e,t,n)=>{"use strict";n.r(t),n.d(t,{default:()=>m});n(92222),n(24812),n(89554),n(54747),n(41539),n(88674),n(74916),n(15306),n(68309),n(69070),n(68304),n(30489),n(12419),n(78011),n(79753),n(82526),n(41817),n(32165),n(66992),n(78783),n(33948),n(47042),n(91038);var r=n(67931),o=n(51474);function i(e){return(i="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function a(e,t){return function(e){if(Array.isArray(e))return e}(e)||function(e,t){var n=e&&("undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"]);if(null==n)return;var r,o,i=[],a=!0,l=!1;try{for(n=n.call(e);!(a=(r=n.next()).done)&&(i.push(r.value),!t||i.length!==t);a=!0);}catch(e){l=!0,o=e}finally{try{a||null==n.return||n.return()}finally{if(l)throw o}}return i}(e,t)||function(e,t){if(!e)return;if("string"==typeof e)return l(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);"Object"===n&&e.constructor&&(n=e.constructor.name);if("Map"===n||"Set"===n)return Array.from(e);if("Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n))return l(e,t)}(e,t)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function l(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,r=new Array(t);n<t;n++)r[n]=e[n];return r}function u(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function c(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function s(e,t){return(s=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}function f(e){var t=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(e){return!1}}();return function(){var n,r=p(e);if(t){var o=p(this).constructor;n=Reflect.construct(r,arguments,o)}else n=r.apply(this,arguments);return d(this,n)}}function d(e,t){return!t||"object"!==i(t)&&"function"!=typeof t?h(e):t}function h(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}function p(e){return(p=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function y(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}var m=function(e){!function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&s(e,t)}(l,e);var t,n,r,i=f(l);function l(){var e;u(this,l);for(var t=arguments.length,n=new Array(t),r=0;r<t;r++)n[r]=arguments[r];return y(h(e=i.call.apply(i,[this].concat(n))),"index",null),y(h(e),"boundInjectFields",null),y(h(e),"currentTemplate",null),e}return t=l,(n=[{key:"initialize",value:function(){this.boundInjectFields=this.injectFields.bind(this)}},{key:"connect",value:function(){var e=this;this.index=this.datumTargets.length,this.computePositions();var t={draggable:".datum",handle:".handle",onSort:function(){e.computePositions()}};this.hasTextsHolderTarget&&new o.ZP(this.textsHolderTarget,t),this.hasImagesHolderTarget&&new o.ZP(this.imagesHolderTarget,t)}},{key:"computePositions",value:function(){this.hasTextsHolderTarget&&this.textsHolderTarget.querySelectorAll(".position").forEach((function(e,t){e.value=t+1})),this.hasImagesHolderTarget&&this.imagesHolderTarget.querySelectorAll(".position").forEach((function(e,t){e.value=t+1}))}},{key:"add",value:function(e){var t=this;fetch("/datum/"+e.target.dataset.type,{method:"GET"}).then((function(e){return e.json()})).then((function(e){var n="image"===e.type?t.imagesHolderTarget:t.textsHolderTarget,r=e.html.replace(/__placeholder__/g,t.index);r=r.replace(/__entity_placeholder__/g,t.element.dataset.entity),n.insertAdjacentHTML("beforeend",r),t.index++,t.computePositions()}))}},{key:"remove",value:function(e){e.preventDefault(),e.target.closest(".datum").remove(),this.computePositions()}},{key:"displayFilename",value:function(e){e.target.nextElementSibling.innerHTML=e.target.files[0].name}},{key:"loadCollectionFields",value:function(e){e.preventDefault(),this.injectFields("/datum/load-collection-fields/"+e.target.dataset.collectionId)}},{key:"loadCommonFields",value:function(e){e.preventDefault(),this.injectFields("/datum/load-common-fields/"+e.target.dataset.collectionId)}},{key:"loadTemplateFields",value:function(e){e.preventDefault(),e.target.value!==this.currentTemplate&&(this.currentTemplate=e.target.value,this.datumTargets.forEach((function(e){e.dataset.template&&e.remove()})),""==e.target.value)||this.injectFields("/templates/"+e.target.value+"/fields")}},{key:"injectFields",value:function(e){var t=this;fetch(e,{method:"GET"}).then((function(e){return e.json()})).then((function(e){e.forEach((function(e){var n,r,o,i=!1,l=a(e,3);if(n=l[0],r=l[1],o=l[2],t.labelTargets.forEach((function(e){e.value===r&&(i=!0)})),!1===i){var u="image"==n?t.imagesHolderTarget:t.textsHolderTarget;o=(o=o.replace(/__placeholder__/g,t.index)).replace(/__entity_placeholder__/g,t.element.dataset.entity),u.insertAdjacentHTML("beforeend",o),t.index++}})),t.computePositions()}))}}])&&c(t.prototype,n),r&&c(t,r),l}(r.Controller);y(m,"targets",["datum","label","textsHolder","imagesHolder"])}}]);