psslai

.directive('updateTitle', ['$rootScope', '$timeout',
  function($rootScope, $timeout) {
    return {
      link: function(scope, element) {

        var listener = function(event, toState) {
          var title = '';
          if (toState.title) title = toState.title;

          $timeout(function() {
            element.text(title+' | POS');
          }, 0, false);
        };

        $rootScope.$on('$stateChangeSuccess', listener);
      }
    };
  }
])


//On Custom Class
.directive('cOverflow', ['scrollService', function(scrollService){
    return {
        restrict: 'C',
        link: function(scope, element) {

            if (!$('html').hasClass('ismobile')) {
                scrollService.malihuScroll(element, 'minimal-dark', 'y');
            }
        }
    }
}])


  .directive('changeLayout', function(){

      return {
          restrict: 'A',
          scope: {
              changeLayout: '='
          },

          link: function(scope, element, attr) {

              //Default State
              if(scope.changeLayout === '1') {
                  element.prop('checked', true);
              }

              //Change State
              element.on('change', function(){
                  if(element.is(':checked')) {
                      localStorage.setItem('ma-layout-status', 1);
                      scope.$apply(function(){
                          scope.changeLayout = '1';
                      })
                  }
                  else {
                      localStorage.setItem('ma-layout-status', 0);
                      scope.$apply(function(){
                          scope.changeLayout = '0';
                      })
                  }
              })
          }
      }
  })

.directive('toggleSidebar', function(){

    return {
        restrict: 'A',
        scope: {
            modelLeft: '=',
            modelRight: '='
        },

        link: function(scope, element, attr) {
            element.on('click', function(){
                if (element.data('target') === 'mainmenu') {
                    if (scope.modelLeft === false) {
                        scope.$apply(function(){
                            scope.modelLeft = true;
                        })
                    }
                    else {
                        scope.$apply(function(){
                            scope.modelLeft = false;
                        })
                    }
                }
            })
        }

    }

})


.directive('toggleSubmenu', function(){

    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            element.click(function(){
                element.next().slideToggle(200);
                element.parent().toggleClass('toggled');
            });
        }
    }
})

// =========================================================================
// WAVES
// =========================================================================

// For .btn classes
.directive('btn', function(){
    return {
        restrict: 'C',
        link: function(scope, element) {
            if(element.hasClass('btn-icon') || element.hasClass('btn-float')) {
                Waves.attach(element, ['waves-circle']);
            }

            else if(element.hasClass('btn-light')) {
                Waves.attach(element, ['waves-light']);
            }

            else {
                Waves.attach(element);
            }

            Waves.init();
        }
    }
})

// =========================================================================
// STOP PROPAGATION
// =========================================================================

.directive('stopPropagate', function(){
    return {
        restrict: 'C',
        link: function(scope, element) {
            element.on('click', function(event){
                event.stopPropagation();
            });
        }
    }
})

.directive('aPrevent', function(){
    return {
        restrict: 'C',
        link: function(scope, element) {
            element.on('click', function(event){
                event.preventDefault();
            });
        }
    }
})


// =========================================================================
// PRINT
// =========================================================================

.directive('print', function(){
    return {
        restrict: 'A',
        link: function(scope, element){
            element.click(function(){
                window.print();
            })
        }
    }
})

.directive('fgLine', function(){
    return {
        restrict: 'C',
        link: function(scope, element) {
            if($('.fg-line')[0]) {
                $('body').on('focus', '.form-control', function(){
                    $(this).closest('.fg-line').addClass('fg-toggled');
                })

                $('body').on('blur', '.form-control', function(){
                    var p = $(this).closest('.form-group');
                    var i = p.find('.form-control').val();

                    if (p.hasClass('fg-float')) {
                        if (i.length == 0) {
                            $(this).closest('.fg-line').removeClass('fg-toggled');
                        }
                    }
                    else {
                        $(this).closest('.fg-line').removeClass('fg-toggled');
                    }
                });
            }

        }
    }

})



// =========================================================================
// AUTO SIZE TEXTAREA
// =========================================================================

.directive('autoSize', function(){
    return {
        restrict: 'A',
        link: function(scope, element){
            if (element[0]) {
               autosize(element);
            }
        }
    }
})


// =========================================================================
// BOOTSTRAP SELECT
// =========================================================================

.directive('selectPicker', function(){
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            //if (element[0]) {
                element.selectpicker();
            //}
        }
    }
})


// =========================================================================
// INPUT MASK
// =========================================================================

.directive('inputMask', function(){
    return {
        restrict: 'A',
        scope: {
          inputMask: '='
        },
        link: function(scope, element){
            element.mask(scope.inputMask.mask);
        }
    }
})


// =========================================================================
// COLOR PICKER
// =========================================================================

.directive('colordPicker', function(){
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            $(element).each(function(){
                var colorOutput = $(this).closest('.cp-container').find('.cp-value');
                $(this).farbtastic(colorOutput);
            });

        }
    }
})



// =========================================================================
// PLACEHOLDER FOR IE 9 (on .form-control class)
// =========================================================================

.directive('formControl', function(){
    return {
        restrict: 'C',
        link: function(scope, element, attrs) {
            if(angular.element('html').hasClass('ie9')) {
                $('input, textarea').placeholder({
                    customClass: 'ie9-placeholder'
                });
            }
        }
    }
})

.directive('noSpecialChar', function() {
    return {
        require: 'ngModel',
        restrict: 'A',
        link: function(scope, element, attrs, modelCtrl) {
            modelCtrl.$parsers.push(function(inputValue) {
                if (inputValue == null)
                    return ''
                cleanInputValue = inputValue.replace(/[^\w\s]/gi, '');
                if (cleanInputValue != inputValue) {
                    modelCtrl.$setViewValue(cleanInputValue);
                    modelCtrl.$render();
                }
                return cleanInputValue;
            });
        }
    }
})

//Error in this directives
.directive('autofocus', ['$timeout', function($timeout) {
    return {
        restrict: 'A',
        link : function($scope, $element) {
            $timeout(function() {
                $element[0].focus();

                //This is the error: An attempt was made to use an object that is not, or is no longer, usable
                //So I comment out this line
                // $element[0].selectionStart = $element[0].selectionEnd = 10000;
            });
        }
    }
}])
.directive('doFilter', function() {
    return {
        require: 'ngModel',
        restrict: 'A',
        link: function(scope, element, attrs, modelCtrl){
            $(element).on('keypress',function(ev){
                element[0].selectionStart = element[0].selectionStart;
            });
            var type    =   attrs.doFilter;
            function doScape(method, val){
                if(method == 'alnum'){ return val.replace(/[`~!@#$%^&*|+\=?;:,<>\{\}\[\]\\\/]/gi,''); }
                else if(method == 'alnum-ext'){ return val.replace(/[`~!@#$%^&*()_|+\=?;:'",<>\{\}\[\]\\\/]/gi,''); /* extended to : - */ }
                else if(method == 'phone'){ return val.replace(/[`~!@#$%^&*_|\=?;:'",a-zA-Z<>\{\}\[\]\\]/gi,''); }
                else if(method == 'address'){ return val.replace(/[`~!$%^*_|\=?;:'"<>\{\}\[\]\\\/]/gi,''); }
                else if(method == 'number'){ return val.replace(/[^0-9]+/g,''); }
                else if(method == 'accountNo'){ return val.replace(/[^0-9_-]+/g,''); }
                else if(method == 'srp'){ return val.replace(/[^0-9.]+/g,''); }
                else if(method == 'amount'){
                    return val.replace(/[^0-9.,-]+/g,'');
                }
                else{
                    return val.replace(/[`~!@#$%^&*|+\=?;:,<>\{\}\[\]\\\/]/gi,'');
                }
            }
            modelCtrl.$parsers.push(function(inputValue) {
                var lasPos  =   element[0].selectionStart;
                if (inputValue == null){ return ''; }
                cleanInputValue =   doScape(type, inputValue);
                if (cleanInputValue != inputValue) {
                    modelCtrl.$setViewValue(cleanInputValue);
                    modelCtrl.$render();
                    element[0].selectionEnd = lasPos - 1;
                }
                return cleanInputValue;
            });
        }
    }
})
.directive('virus',function(){
    return {
        restrict: 'A',
        link: function(scope, element, attrs){
            $(element).on('paste drop',function(ev){  ev.preventDefault(); });
        }
    }
})

.directive('please',function(){
    return {
        restrict: 'A',
        scope: {
            testFunc: '&'
        },
        link: function(scope, element, attrs){
            console.log("asdasd");
            element.bind('click',function(){
                console.log("asdasds");
                scope.testFunc()("asdasdsad");
            })
        }
    }
})
.directive('cocContent',function(){
    return {
        restrict : "E",
        scope    : {
            listcoc : "=",
        },
        controllerAs : "custom",
        template : "<div class='searchDivcoc z-depth-1' ng-if='listcoc.length != 0' ><div class='list-group'><a href='' ng-click='selectcoc(item,$event)' ng-repeat='item in listcoc' class='list-group-item'>{{item.code}}</a></div></div>",
        controller : function($scope){
            $scope.selectcoc = function(obj,e){
                console.log("asdasdasd");
                angular.element(".coc-value").attr('data-id',obj.key);
                angular.element(".coc-value").attr('data-crypt',obj.crypt);
                angular.element(".coc-value").val(obj.code);
                e.preventDefault();
            }
        }
    }
})

.directive('podContent',function(){
    return {
        restrict : "E",
        scope    : {
            listpod : "=",
        },
        controllerAs : "custom",
        template : "<div class='searchDivpod z-depth-1' ng-if='listpod.length != 0' ><div class='list-group'><a href='' ng-click='selectpod(item,$event)' ng-repeat='item in listpod' class='list-group-item'>{{item.code}}</a></div></div>",
        controller : function($scope){
            $scope.selectpod = function(obj,e){
                angular.element(".pod-value").attr('data-id',obj.key);
                angular.element(".pod-value").attr('data-crypt',obj.crypt);
                angular.element(".pod-value").val(obj.code);
                e.preventDefault();
            }
        }
    }
})


.directive('pdContent',function(){
    return {
        restrict : "E",
        scope    : {
            listpd : "=",
        },
        controllerAs : "custom",
        template : "<div class='searchDivpd z-depth-1' ng-if='listpd.length != 0' ><div class='list-group'><a href='' ng-click='selectpd(item,$event)' ng-repeat='item in listpd' class='list-group-item'>{{item.code}}</a></div></div>",
        controller : function($scope){
            $scope.selectpd = function(obj,e){
                console.log("sadasdas");
                angular.element(".pd-value").attr('data-id',obj.key);
                angular.element(".pd-value").attr('data-crypt',obj.crypt);
                angular.element(".pd-value").val(obj.code);
                e.preventDefault();
            }
        }
    }
})

.directive('timeInput', function(){
    return {
        require: 'ngModel',
        restrict: 'A',
        link: function(scope, element, attrs, modelCtrl){
            modelCtrl.$parsers.push(function(inputValue){
                if (inputValue == null){
                    return '';
                }
                else{
                    $(element).inputmask(
                        "HH:mm:ss",
                        {
                           placeholder: "HH:MM:SS",
                           insertMode: false,
                           showMaskOnHover: false,
                           hourFormat: 24,
                           mask: "h:s:s",
                        }
                    );
                    modelCtrl.$setViewValue(inputValue);
                    modelCtrl.$render();
                }
                return inputValue;
            });
        }
    }
})
.directive('putCode',function(){
    return {
        restrict : "A",
        scope    : {
            item    : "=",
            index   : "=",
            typeval : "="
        },
        link : function(scope,element,attrs){
            element.bind('click',function(e){
                if (scope.typeval == "pc"){
                    var input = element.parents(".form-group").find(".pc-value"+scope.index)
                    input.val(scope.item.code);
                    input.attr('data-id',scope.item.key);
                    input.attr('data-crypt',scope.item.crypt);
                    angular.element("#autoindex"+scope.index+"pc").html("");
                }
                if (scope.typeval == "bl"){
                    var input = element.parents(".form-group").find(".bl-value"+scope.index)
                    input.val(scope.item.code);
                    input.attr('data-id',scope.item.key);
                    input.attr('data-crypt',scope.item.crypt);
                    angular.element("#autoindex"+scope.index+"bl").html("");
                }
                e.preventDefault();
            })
        }
    }
})

.directive('testKill',function(){
    return {
        restrict : "A",
        scope    : {
            callback   : "&myFunction",
        },
        link : function(scope,element,attrs){
            // my-function
            element.bind('blur',function(){
                console.log("");
                scope.callback();
            })
        }
    }
})

.directive('changeColor',function(){
    return {
        restrict : "A",
        scope    : {
            choosecolor  : "=",
        },
        link : function(scope,element,attrs){
            console.log(scope.choosecolor);
            element.css("background-color",scope.choosecolor)
        }
    }
})

.directive('nameOnly', function() {
    return {
        require: 'ngModel',
        restrict: 'A',
        link: function(scope, element, attrs, modelCtrl) {;
            modelCtrl.$parsers.push(function(inputValue) {
                if (inputValue == null)
                    return ''

                cleanInputValue = inputValue.replace(/[^\u00F1\u00D1a-zA-Z,.-\s]+/g, '');
                if (cleanInputValue != inputValue) {
                    modelCtrl.$setViewValue(cleanInputValue);
                    modelCtrl.$render();
                }
                return cleanInputValue;
            });
        }
    }
})

// date input Validations
.directive('dateInput', function() {
    return {
        require: 'ngModel',
        restrict: 'A',
        link: function(scope, element, attrs, modelCtrl) {
            modelCtrl.$parsers.push(function(inputValue) {
                if (inputValue == null) {
                    return '';
                } else {
                    $(element).inputmask({
                        alias: 'mm-dd-yyyy',
                        placeholder: "MM-DD-YYYY",
                        showMaskOnHover: false,
                    });

                    modelCtrl.$setViewValue(inputValue);
                    modelCtrl.$render();
                }
                return inputValue;
            });
        }
    }
})

// =========================================================================
    // INPUT MASK
    // =========================================================================

    .directive('inputMask', function(){
        return {
            restrict: 'A',
            scope: {
              inputMask: '='
            },
            link: function(scope, element){
                element.mask(scope.inputMask.mask);
            }
        }
    })

    .directive('emailOnly', function() {
        return {
            require: 'ngModel',
            restrict: 'A',
            link: function(scope, element, attrs, modelCtrl) {
                modelCtrl.$parsers.push(function(inputValue) {
                    // console.log(inputValue, "TEST");
                    if (inputValue == null) {
                        return '';
                    }

                    cleanInputValue = inputValue.replace(/[\s]/gi, '');

                    if (cleanInputValue != inputValue) {
                        modelCtrl.$setViewValue(cleanInputValue);
                        modelCtrl.$render();
                    }
                    return cleanInputValue;
                });
            }
        }
    })

    .directive('emailValidate', function() {
        return {
            require: 'ngModel',
            restrict: 'A',
            link: function(scope, element, attr, mCtrl) {
                function myValidation(value) {
                    element.bind('blur', function(event) {
                        if (value.indexOf(".com") > -1) {
                            document.getElementById("emailAddress").style.borderColor = "#ccc";
                        } else {
                            document.getElementById("emailAddress").style.borderColor = "red";
                        }
                        return value;
                    });

                }
                mCtrl.$parsers.push(myValidation);
            }
        };
    })


    .directive('uppercaseOnly', [function() {
        return {
            restrict: 'A',
            require: 'ngModel',
            link: function(scope, element, attrs, ctrl) {
                element.on('keypress', function(e) {
                    var char = e.char || String.fromCharCode(e.charCode);
                });

                function parser(value) {
                    if (ctrl.$isEmpty(value)) {
                        return value;
                    }
                    var formatedValue = value.toUpperCase();
                    if (ctrl.$viewValue !== formatedValue) {
                        ctrl.$setViewValue(formatedValue);
                        ctrl.$render();
                    }
                    return formatedValue;
                }

                function formatter(value) {
                    if (ctrl.$isEmpty(value)) {
                        return value;
                    }
                    return value.toUpperCase();
                }

                ctrl.$formatters.push(formatter);
                ctrl.$parsers.push(parser);
            }
        };
    }])

    .directive('noSpecialChar', function() {
        return {
            require: 'ngModel',
            restrict: 'A',
            link: function(scope, element, attrs, modelCtrl) {
                modelCtrl.$parsers.push(function(inputValue) {
                    if (inputValue == null)
                        return ''

                    cleanInputValue = inputValue.replace(/[^\w\s\u00F1\u00D1]/gi, '');
                    if (cleanInputValue != inputValue) {
                        modelCtrl.$setViewValue(cleanInputValue);
                        modelCtrl.$render();
                    }
                    return cleanInputValue;
                });
            }
        }
    })

    .directive('noSpecialCharExceptDotComma', function() {
        return {
            require: 'ngModel',
            restrict: 'A',
            link: function(scope, element, attrs, modelCtrl) {
                modelCtrl.$parsers.push(function(inputValue) {
                    if (inputValue == null)
                        return ''

                    cleanInputValue = inputValue.replace(/[^\w\s\u00F1\u00D1.,]/gi, '');
                    if (cleanInputValue != inputValue) {
                        modelCtrl.$setViewValue(cleanInputValue);
                        modelCtrl.$render();
                    }
                    return cleanInputValue;
                });
            }
        }
    })

    .directive("fileInput", function($parse){
        return{
             link: function($scope, element, attrs){
                  element.on("change", function(event){
                       var files = event.target.files;
                       $parse(attrs.fileInput).assign($scope, element[0].files);
                       $scope.$apply();
                  });
             }
        }
   })
