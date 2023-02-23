document.addEventListener("DOMContentLoaded", function (event) {   
    
    // Подставляем код телефона
    function addMaskPhone() {
      var mask = {
        getCountrySelects: function () {
          var selects = document.querySelectorAll('.country_select');
          return (selects.length) ? selects : 0;
        },

        getPhones: function () {
          var phones = document.querySelectorAll('.wv_phone');
          return (phones.length) ? phones : 0;
        },

        init: function () {
          var selects = mask.getCountrySelects();
          var phones = mask.getPhones();
          if (selects && phones) {

            //выставляем дефолтный код
            var countryCode = selects[ 0 ].value.toLowerCase();
            var countryCodes = {
              'at': '+43',
              'ch': '+41',
              'de': '+49',
              'it': '+39',
              'es': '+34',
              'lv': '+371',
              'lt': '+370',
              'ee': '+372',
              'ro': '+40',
              'bg': '+359',
              'pl': '+48',
              'gr': '+30',
              'cy': '+357',
              'hu': '+36',
              'fr': '+33',
              'cz': '+420',
              'pt': '+351',
              'ru': '+7',
            };

            selects.forEach(function (select) {
              select.addEventListener('change', function () {
                countryCode = this.value;
                selects.forEach(function (sel) {
                  sel.value = countryCode;
                });
              });
            });

            phones.forEach(function (phone) {
              phone.pattern = '(\\+)[0-9]{11,16}';
              phone.title = 'the phone must contain 12 to 17 digits only';

              //при попадании фокуса оставляем код + введенную часть номера
              phone.addEventListener('focusin', function () {
                var code = countryCodes[ countryCode.toLowerCase() ];
                this.value = !(this.value.length > code.length) ? code : this.value;
              });

              //при вводе блокируем удаление кода страны
              phone.addEventListener('input', function () {
                var code = countryCodes[ countryCode.toLowerCase() ];
                this.value.indexOf(code) && (this.value = code);
              });
            });
          }
        },
      };

      mask.init();

    }
  addMaskPhone();
});
