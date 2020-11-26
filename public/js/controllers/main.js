psslai
    .controller('streakUXCtrl', ['$timeout','$rootScope', '$state', '$scope','$location','$window','$compile','modalService','growlService','helper','$http',function($timeout,$rootScope, $state, $scope,$location,$window,$compile,modalService,growlService, helper,$http){

        // DEFAULT LANDING
        $rootScope.landing = window.localStorage.getItem('currentUser') == null ? '/login' : '/home';
        if($rootScope.landing == '/home'){
          $window.location.href = baseUrl + "#/home";
        }

        $scope.tryJson  =   function(){
            try {
                var o = JSON.parse(jsonString);
                if (o && typeof o === "object") {
                    return true;
                }
            }
            catch (e) {
                $window.localStorage.clear();
                return false;
            }
            $window.localStorage.clear();
            return false;
        }
        $scope.user_info = $window.localStorage.currentUser != null ? JSON.parse($window.localStorage.currentUser) : {};
        this.$state = $state;
        $scope.modal_action =   'New';
        
       
        // Detact Mobile Browser
        if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
           angular.element('html').addClass('ismobile');
        }

        //Skin Switch
        localStorage.setItem('ux-currentSkin', 'blue');
        this.currentSkin = localStorage.getItem('ux-currentSkin') == null ? 'lightblue' : localStorage.getItem('ux-currentSkin');

        this.skinList = ['lightblue','bluegray','cyan','teal','green','orange','blue','purple'];

        this.skinSwitch = function (color) {
            this.currentSkin = color;
            localStorage.setItem('ux-currentSkin',color);

            // CHANGE SIDEBAR COLOR
            $('.main-menu a[data-current-skin]').attr('data-current-skin', localStorage.getItem('ux-currentSkin'))
        }

        $rootScope.checkIfButtonAllowed = function(module,button) {
            $result = false;
            $scope.getUserInfo = $window.localStorage.currentUser != null ? JSON.parse($window.localStorage.currentUser) : {};
            angular.forEach($scope.getUserInfo.user_role,function(e){
                angular.forEach(e.sub,function(x){
                    if(x.module_name===module){
                        if(x[button]=="1"){
                            $result = true;
                        }
                        else{
                            $result = false;
                        }
                    }
                });
            });
            return $result;
        }

        $scope.compile = function(element){
            var el = angular.element(element);
            $scope = el.scope();
              $injector = el.injector();
              $injector.invoke(function($compile){
                 $compile(el)($scope)
              })
        };

        $rootScope.cumaDatabase = [];
        $rootScope.chooseKey   = 0;
        $rootScope.chooseCrypt = "";
        $rootScope.isUseFileOk  = false;
        $rootScope.isFromCoversion  =   false;

        $scope.emailValidator = function(email_address) {
          if(!email_address){return;}
           var emailCheck=/^[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i;
           return emailCheck.test(email_address);
        };

        $scope.$on('modal.closing', function(event, reason, closed) {
            $scope.key = 0;
        });

        $scope.noSpaces = function($event){
           if ($event.which == 32) {
               $event.preventDefault();
           }
       }
       $rootScope.delimeter = "|%^&|";
        $scope.hexc = function(colorval) {
            var colorval = angular.element("#header").css('backgroundColor');
            var parts = colorval.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            delete(parts[0]);
            for (var i = 1; i <= 3; ++i) {
               parts[i] = parseInt(parts[i]).toString(16);
               if (parts[i].length == 1) parts[i] = '0' + parts[i];
            }
            return '#' + parts.join('');
        }

        $rootScope.getToken = function(){
         return token = {
           name : $('#form_token').attr('name'),
           value : $('#form_token').val()
         };
       }

       $scope.doLogout = function(){
            $http({
                method: 'GET',
                url: baseUrl + '/index/out',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            })
            .then(
                function successCallback(response){
                    var data    =   response.data;
                    if(data.statusCode === 200){
                        $window.localStorage.clear();
                        $window.location.href = baseUrl + "#/login";
                        window.location.reload(true);
                    }
                    else{
                        growlService.growl(data.devMessage,'warning');
                    }
                },
                function errorCallback(response){
                    growlService.growl('Something went wrong. Please reload the page and try again. additional: <span class="error">'+response.statusText+'</span>','warning');
                }
            );

       }


        this.sidebarStat = function(event) {
            $('.main-menu a').removeAttr('data-current-skin');
            var el = angular.element(event.target);
            el.attr('data-current-skin', localStorage.getItem('ux-currentSkin'))
        }


        this.lvMenuStat = false;

       this.sidebarToggle = {
           left: false,
           right: false
       }
        this.layoutType = localStorage.getItem('ma-layout-status');
        $scope.open = function(template) {
           modalService.modalInstances(true, 'lg', true, true, template,$scope);
       };
        $scope.setRows   =   function(newRow, ev){
           row = newRow;
           var parent = helper.getParent('rows-setter-page', ev.target, 'id');
           $(parent).find('button.active').css('pointer-events','auto');
           $(parent).find('button.active').removeClass('active');
           ev.target.classList.add('active');
           ev.target.style.pointerEvents = 'none';
       }
        $scope.alert     =   function(message,width,isButtonShow, btnText){
            var w   =   window.innerWidth, finalWidth;
            if(w > 767){ finalWidth = 500; }
            else { finalWidth = 310; }

            if(width != null && $.isNumeric(width)){finalWidth = width;}
            var date = new Date();
            var time = date.getTime();
            var html = '';
                html += '<div id="'+time+'-crm-alert" class="crm-alert crm-dialog">';
                    html += '<div style="width: '+finalWidth+'px; height: auto;" class="crm-dialog-container">';
                        html += '<div class="crm-dialog-text-container">';
                            html += message;
                        html += '</div>';
                        if(isButtonShow == 'yes' || isButtonShow == null){
                            html += '<div class="text-right crm-dialog-buttons crm-alert-button">';
                                html += '<button class="btn btn-link crm-alert-accept waves-effect" style="width:60px;">'+(btnText == null ? 'Ok' : btnText)+'</button>';
                            html += '</div>';
                        }
                    html += '</div>';
                html += '</div>';
                $('body').append($($compile(html)($scope)).fadeIn("fast"));
                $('body').css("overflow-y",'hidden');
                $(".crm-alert-accept").on("click",function(){
                    $("#"+time+"-crm-alert > div:first-child").slideUp("fast",function(){
                        setTimeout(function(){
                            $("#"+time+"-crm-alert").remove();
                            var len =  $("body").find(".crm-dialog").length;
                            if(len == 0){$('body').css("overflow",'visible');}
                        },100);
                    });
                });
        }
        $scope.closeAlert=   function(ev){
            angular.element('.crm-dialog > div:first-child').slideUp('fast');
            setTimeout(function(){
                angular.element('.crm-dialog').remove();
                document.body.style.overflow = 'auto';
            },200);
        }

        $scope.datepicker = {};
        $scope.today = function() {
            return new Date();
        };
        $scope.dtPopup = $scope.today();
        $scope.openCalendar=function($event, opened) {
            $event.preventDefault();
            $event.stopPropagation();
            $scope.datepicker[opened] = true;
        };
        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };
        $scope.formats = ['dd-MMMM-yyyy', 'yyyy/MM/dd', 'dd.MM.yyyy','yyyy/MM/dd','shortDate'];
        $scope.format = $scope.formats[3];

        $scope.closeModal = function() {
            $rootScope.cancel();
        }

        // CUSTOM PAGER - Ehddver Cabiten
        $rootScope.customPager = function(scope, tableNgModel, end = false){
          setTimeout(function(){
            var tableModel = scope.$eval(tableNgModel);
            angular.element('ul.ng-table-pagination li').remove();
            scope.nextPage = tableModel.page() + 1;
            scope.prevPage = (tableModel.page() - 1 == 0 ? 1 : tableModel.page() - 1);

            // PREVIOUS CLICK
            scope.p = function(){
              tableModel.page(scope.prevPage);
            }
            // NEXT CLICK
            scope.n = function(){
              tableModel.page(scope.nextPage);
            }

            // APPEND PREV / NEXT
            var dataLength = $('table[ng-table='+ tableNgModel +'] tbody tr').length;
            var disabledLeft = (dataLength <= 9 && tableModel.page() == 1) ? 'disabled' : (tableModel.page() == 1 ? 'disabled' : '');
            var disabledRight = (dataLength <= 9) ? 'disabled' : '';

            angular.element('ul.ng-table-pagination').append($compile('<li class="_customPrev '+ disabledLeft +' " ng-click="p();"><a ng-click="p();" href="" class="ng-scope"></a></li>')(scope))
            angular.element('ul.ng-table-pagination').append($compile('<li class="_customNext '+ disabledRight +' " ng-click="n();"><a ng-click="n();" href="" class="ng-scope"></a></li>')(scope));

            // LAST RECORD
            if(end){
              tableModel.page(scope.lastData);
            }
            if(scope.lastData == tableModel.page()){
              if(scope.lastData != 1){
                setTimeout(function(){
                  angular.element('._customNext').addClass('disabled');
                  growlService.growl("This is the last record","success");
                }, 100);
              }
              if(scope.lastData == 1){
                growlService.growl("This is the last record","success");
              }
            }

          }, 50);

        }

        // LATEST SIDEBAR UPDATES - Ehddver Cabiten
        setTimeout(function(){
          if(window.location.href.indexOf('home')){
            // REMOVE ACTIVE LINK
            $rootScope.sideMenu.child = "";
            // CLEAR LOCAL STORAGE
            localStorage.removeItem('activeParentAccordion');
            localStorage.removeItem('activeChildAccordion');
            localStorage.removeItem('activeSubChildAccordion');
          }
      }, 700);

        var activeParentAccordion = localStorage.getItem('activeParentAccordion');
        var activeChildAccordion = localStorage.getItem('activeChildAccordion');
        var activeSubChildAccordion = localStorage.getItem('activeSubChildAccordion');
        $rootScope.sideMenu = {};
        $rootScope.skin = localStorage.getItem('ux-currentSkin') == null ? 'lightblue' : localStorage.getItem('ux-currentSkin');
        $rootScope.sideMenu.parent = (activeParentAccordion == null || activeParentAccordion == null) ? "" : activeParentAccordion;
        $rootScope.sideMenu.child = (activeChildAccordion == null || activeChildAccordion == null) ? "" : activeChildAccordion;
        $rootScope.sideMenu.subChild = (activeSubChildAccordion == null || activeSubChildAccordion == null) ? "" : activeSubChildAccordion;
        $rootScope.toggleAccordion = function($event){
          $rootScope.sideMenu.parent = $event.target.text.toLowerCase().trim().replace(/ /g, '_');
          localStorage.setItem('activeParentAccordion', $rootScope.sideMenu.parent);
        }
        $rootScope.toggleChildAccordion = function($event, accordion_element = false){
          $event.stopPropagation();
          if(!accordion_element){
            $rootScope.sideMenu.child = $event.target.text.toLowerCase().trim().replace(/ /g, '_');
            localStorage.setItem('activeChildAccordion', $rootScope.sideMenu.child);
          }
          else{
            $rootScope.sideMenu.subChild = $event.target.text.toLowerCase().trim().replace(/ /g, '_');
            localStorage.setItem('activeSubChildAccordion', $rootScope.sideMenu.subChild);

            // console.log($rootScope.sideMenu.subChild);
          }
        }

        //Setting of role access for the users
        //Modules
        $scope.filings      = false;

        //Filing access role
        $rootScope.filingRole = {
            loanBilling: {
                view: false,
                delete: false,
                import: false
            },
            loanAtm: {
                view: false,
                delete: false,
                import: false
            },
            memInfo: {
                view: false,
                delete: false,
                import: false
            },
            memAcc: {
                view: false,
                delete: false,
                import: false
            },
            atmList: {
                view: false,
                delete: false,
                import: false
            },
            collection: {
                view: false,
                delete: false,
                import: false
            },
            sdlis: {
                view: false,
                delete: false,
                import: false
            }

        };

        //Membership
        $rootScope.membershipRole = {
            access: {
                view: false,
            }
        }

        //ATM
        $rootScope.atmRole = {
            access: {
                view: false,
                pin: false,
                export: false,
            }
        }

        //Billing
        $rootScope.billingRole = {
            access: {
                view: false,
                export: false,
            }
        }

        //Setting
        $rootScope.settingRole = {
            access: {
                view: false,
                add: false,
                edit: false,
                delete: false,
            }
        }

        //User Manangement
        $rootScope.userRole = {
            access: {
                view: false,
                add: false,
                edit: false,
                delete: false,
            }
        }

        if($scope.user_info.user_role != undefined) {
            for(let m = 0; m < $scope.user_info.user_role.length; m++) {
                var data = $scope.user_info.user_role[m];
                if(data.head_module_id == 1) {
                    $scope.filings = true;
                    if(data.module_id == 1) {
                        $rootScope.filingRole.loanBilling = {
                            view        : data.role.view,
                            delete      : data.role.dlte,
                            import      : data.role.import,
                        }
                    }
                    else if(data.module_id == 2) {
                        $rootScope.filingRole.loanAtm = {
                            view        : data.role.view,
                            delete      : data.role.dlte,
                            import      : data.role.import,
                        }
                    }
                    else if(data.module_id == 3) {
                        $rootScope.filingRole.memInfo = {
                            view        : data.role.view,
                            delete      : data.role.dlte,
                            import      : data.role.import,
                        }
                    }
                    else if(data.module_id == 4) {
                        $rootScope.filingRole.memAcc = {
                            view        : data.role.view,
                            delete      : data.role.dlte,
                            import      : data.role.import,
                        }
                    }
                    else if(data.module_id == 5) {
                        $rootScope.filingRole.atmList = {
                            view        : data.role.view,
                            delete      : data.role.dlte,
                            import      : data.role.import,
                        }
                    }
                    else if(data.module_id == 6) {
                        $rootScope.filingRole.collection = {
                            view        : data.role.view,
                            delete      : data.role.dlte,
                            import      : data.role.import,
                        }
                    }
                    else if(data.module_id == 7) {
                        $rootScope.filingRole.sdlis = {
                            view        : data.role.view,
                            delete      : data.role.dlte,
                            import      : data.role.import,
                        }
                    }
                }
                else if(data.head_module_id == 2) {
                    if(data.module_id == 8) {
                        $rootScope.billingRole = {
                            access: {
                                view        : data.role.view,
                                export      : data.role.export,
                            }
                        }
                    }

                }
                else if(data.head_module_id == 3) {
                    if(data.module_id == 9) {
                        $rootScope.atmRole = {
                            access: {
                                view        : data.role.view,
                                pin         : data.role.mask,
                                export      : data.role.export,
                            }
                        }
                    }

                }
                else if(data.head_module_id == 4) {
                   if(data.module_id == 10) {
                        $rootScope.settingRole = {
                            access: {
                                view        : data.role.view,
                                add         : data.role.add,
                                edit        : data.role.edit,
                                delete      : data.role.dlte,
                            }
                        }
                   }
                }
                else if(data.head_module_id == 5) {
                    if(data.module_id == 11) {
                        $rootScope.membershipRole = {
                            access: {
                                view: data.role.view,
                            }
                        }
                    }
                }
                else if(data.head_module_id == 6) {
                    // console.log(data.module_id, "USER   ");
                    if(data.module_id == 12) {
                        $rootScope.userRole = {
                            access: {
                                view        : data.role.view,
                                add         : data.role.add,
                                edit        : data.role.edit,
                                delete      : data.role.dlte,
                            }
                        }
                    }
                }
            }
        }

        $rootScope.downloadTemplate = function (template) {
            var filePath = "TEMPLATE/"+template;
            var link=document.createElement('a');
            link.target = "_blank";
            link.href = filePath;
            link.download = filePath.substr(filePath.lastIndexOf('/') + 1);
            link.click();
        }

    }])


    .controller('ModalInstanceCtrl', ['$rootScope', '$scope', '$uibModalInstance', 'content',function ($rootScope, $scope, $uibModalInstance, content) {
          $rootScope.modalContent = content;

          $rootScope.ok = function () {
            $uibModalInstance.close();
          };

          $rootScope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
          };
    }])


    // =========================================================================
    // Header
    // =========================================================================
    .controller('headerCtrl', ['$http','$timeout','$window','$scope', 'NgTableParams' ,function($timeout,$window,$scope,$http, NgTableParams){

        //Fullscreen View
        this.fullScreen = function() {
            //Launch
            function launchIntoFullscreen(element) {
                if(element.requestFullscreen) {
                    element.requestFullscreen();
                } else if(element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if(element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                } else if(element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                }
            }

            //Exit
            function exitFullscreen() {
                if(document.exitFullscreen) {
                    document.exitFullscreen();
                } else if(document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if(document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }

            if (exitFullscreen()) {
                launchIntoFullscreen(document.documentElement);
            }
            else {
                launchIntoFullscreen(document.documentElement);
            }
        }



    }])

    .controller('LoginModalCtrl', ['$scope','$rootScope','$window','$state', '$http', 'growlService',function ($scope,$rootScope,$window,$state, $http, growlService) {

      this.cancel = $scope.$dismiss;

      this.submit = function (username,password) {
          // if (username == null || password == null) {
          //     swal({
          //                 title: "Please enter valid Email Address and Password",
          //                 type: "warning",
          //                 confirmButtonClass: "btn-success",
          //                 confirmButtonText: "OK",
          //                 closeOnConfirm: true
          //         });
          //     return;
          // }

        var token = $rootScope.getToken();

        $http({
            method: 'POST',
            url: baseUrl + '/index/auth',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            data : {
              username              : username,
              password              : password,
              token_key             : token.name,
              token_value           : token.value
            }
        }).then(function successCallback(response){
             if(response.data.error_code == 0){
                 localStorage.setItem('ma-layout-status', 1);
                 $scope.$close(response.data.devMessage);
             }else{
                  $scope.login_invalid = response.data.msg;
             }
        },function errorCallback(response){
           growlService.growl(response.statusText,'danger');
        });

      };

    }])

    .controller('HomeCtrl', ['$scope','$rootScope','$window','$state', '$http', 'growlService',function ($scope,$rootScope,$window,$state, $http, growlService) {


    }])

    // .controller('UserCtrl', ['$scope','$rootScope','$window','$state', '$http', 'growlService','NgTableParams',function ($scope,$rootScope,$window,$state, $http, growlService, NgTableParams) {
    //
    //     $scope.getUsers = function(){
    //         $scope.usersTable = new NgTableParams({
    //             sorting : {
    //                 id    :   'asc',
    //             },
    //             page    :   1,
    //             count   :   10,
    //             },{
    //                 total   :   0,
    //                 getData : function($defer, params) {
    //                     var page   =    params.page();
    //                     var count  =    params.count();
    //
    //                     $http({
    //                         method  : 'POST',
    //                         headers: {
    //                             'Content-Type'  : 'application/x-www-form-urlencoded; charset=UTF-8'
    //                         },
    //                         url:  baseUrl + '/users/getUsers',
    //                         data  :   JSON.stringify({
    //                             page       : page,
    //                             count      : count,
    //                             orderBy    : params.orderBy()[0]
    //                         })
    //
    //                     }).then(function successCallback(response) {
    //                         $scope.users = response.data.userList == null ? [] : response.data.userList;
    //                         $scope.usersTable.total(response.data.total);
    //                         $defer.resolve($scope.users);
    //                     }, function errorCallback(response){
    //                     });
    //                 }
    //
    //             });
    //     }
    //
    //
    // }])

    .controller('UserRoleCtrl', ['helper','$timeout','$scope','$rootScope','$window','$state', '$http', 'growlService','processExcelFile','NgTableParams','modalService','createModal', 'inputChecker',
        function (helper, $timeout, $scope,$rootScope,$window,$state, $http, growlService, processExcelFile, NgTableParams, modalService,createModal, inputChecker) {
        $scope.form                 = {};
        $scope.user                 = {};
        $scope.user.roles           = {};
        $scope.user.modules         = {};
        $scope.user.maintenance     = {};
        $scope.user.list            = {};
        $scope.user.id_key          = "";
        $scope.user.edit_roles      = {};
        $scope.user.access          = {};

        $scope.addUserRole = function(){
            console.log(angular.element("#UserManagementView_12").prop("checked", false));

            $scope.user.roles.name      = "";
            $scope.user.maintenance     = {};
            $scope.user.id_key          = "";
            $scope.getSystemModules();
            setTimeout(function() {
                createModal.modalInstances(true, 'm', 'static', false, $scope.modalContent, 'addUserRoles', $scope);
            }, 100);
        }

        $scope.editUserRole = function(key){
            $scope.user.maintenance     = {};
            $scope.user.access          = {};
            $scope.getSystemModules();
            $scope.getRoleSpecific(key.key).then(function(){
                $scope.user.id_key          = key.key;
                $scope.user.roles.name      = key.user_role;

                createModal.modalInstances(true, 'm', 'static', false, $scope.modalContent, 'editUserRoles', $scope);

                var checkExistingElement = setInterval(function() {
                    if($("#table-edit-roles tbody tr td div div label").is(":visible")) {
                        clearInterval(checkExistingElement);

                        if($scope.user.access.filings.length > 0) {

                            for(let filing = 0; filing < $scope.user.access.filings.length; filing++){
                                var id = $scope.user.access.filings[filing].module_id;
                                var roles_access        = $scope.user.access.filings[filing].role;
                                var id_key_delete       = "#FilingDelete_" + id;
                                var id_key_view         = "#FilingView_" + id;
                                var id_key_import       = "#FilingImport_" + id;

                                angular.element(id_key_delete)[0].checked       = roles_access.dlte;
                                angular.element(id_key_view)[0].checked         = roles_access.view;
                                angular.element(id_key_import)[0].checked       = roles_access.import;
                            }

                        }

                        if($scope.user.access.bilings.length > 0) {
                            for(let biling = 0; biling < $scope.user.access.bilings.length; biling++){
                                var id = $scope.user.access.bilings[biling].module_id;
                                var roles_access        = $scope.user.access.bilings[biling].role;
                                var id_key_view         = "#BillingView_" + id;
                                var id_key_export       = "#BillingExport_" + id;

                                angular.element(id_key_view)[0].checked         = roles_access.view;
                                angular.element(id_key_export)[0].checked       = roles_access.export;
                            }
                        }

                        if($scope.user.access.atms.length > 0) {
                            for(let atm = 0; atm < $scope.user.access.atms.length; atm++){
                                var id = $scope.user.access.atms[atm].module_id;
                                var roles_access        = $scope.user.access.atms[atm].role;
                                var id_key_add          = "#ATMAdd_" + id;
                                var id_key_pin          = "#PinView_" + id;
                                var id_key_export       = "#ATMExport_" + id;

                                angular.element(id_key_add)[0].checked          = roles_access.view;
                                angular.element(id_key_pin)[0].checked          = roles_access.mask;
                                angular.element(id_key_export)[0].checked       = roles_access.export;
                            }
                        }

                        if($scope.user.access.settings.length > 0) {
                            for(let setting = 0; setting < $scope.user.access.settings.length; setting++){
                                var id = $scope.user.access.settings[setting].module_id;
                                var roles_access        = $scope.user.access.settings[setting].role;
                                var id_key_add          = "#SettingsAdd_" + id;
                                var id_key_edit         = "#SettingsEdit_" + id;
                                var id_key_delete       = "#SettingsDelete_" + id;
                                var id_key_view         = "#SettingsView_"+id;

                                angular.element(id_key_add)[0].checked          = roles_access.add;
                                angular.element(id_key_edit)[0].checked          = roles_access.edit;
                                angular.element(id_key_delete)[0].checked       = roles_access.dlte;
                                angular.element(id_key_view)[0].checked       = roles_access.view;
                            }
                        }

                        if($scope.user.access.memberships.length > 0) {
                            for(let membership = 0; membership < $scope.user.access.memberships.length; membership++){
                                var id = $scope.user.access.memberships[membership].module_id;
                                var roles_access        = $scope.user.access.memberships[membership].role;
                                var id_key_view          = "#MembershipView_" + id;

                                angular.element(id_key_view)[0].checked       = roles_access.view;
                            }
                        }

                        if($scope.user.access.user_setup.length > 0) {
                            for(let setup = 0; setup < $scope.user.access.user_setup.length; setup++){
                                var id = $scope.user.access.user_setup[setup].module_id;
                                var roles_access         = $scope.user.access.user_setup[setup].role;
                                var id_key_add           = "#UserManagementAdd_" + id;
                                var id_key_edit          = "#UserManagementEdit_" + id;
                                var id_key_delete        = "#UserManagementDelete_" + id;
                                var id_key_view          = "#UserManagementView_" + id;

                                angular.element(id_key_add)[0].checked        = roles_access.add;
                                angular.element(id_key_edit)[0].checked       = roles_access.edit;
                                angular.element(id_key_delete)[0].checked     = roles_access.dlte;
                                angular.element(id_key_view)[0].checked       = roles_access.view;
                            }
                        }
                    }
                }, 100);
            });
        }

        $scope.getSystemModules = function(){
            $http({
                method  : 'POST',
                headers: {
                    'Content-Type'  : 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                url:  baseUrl + '/users/getSystemModules',
                data  :   JSON.stringify({

                })
            }).then(function successCallback(response) {
                    if(response.data.statusCode ==  200){
                        // $scope.user.modules.list = response.data.devMessage;

                        //These are the complete list of modules coming from the database.
                        $scope.user.modules.test = "TEST";
                        $scope.user.modules.filings              =  response.data.devMessage[0];
                        $scope.user.modules.billings             =  response.data.devMessage[1];
                        $scope.user.modules.atms                 =  response.data.devMessage[2];
                        $scope.user.modules.settings             =  response.data.devMessage[3];
                        $scope.user.modules.memberships          =  response.data.devMessage[4];
                        $scope.user.modules.user_managements     =  response.data.devMessage[5];
                    }
            }, function errorCallback(response){
            });
        }

        $scope.getUsers = function(){
            $http({
                method  : 'POST',
                headers: {
                    'Content-Type'  : 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                url:  baseUrl + '/users/getUsersList',
                data  :   JSON.stringify({

                })
            }).then(function successCallback(response) {
                    if(response.data.statusCode==200){
                        $scope.system_modules_list = response.data.devMessage;
                    }
            }, function errorCallback(response){
            });
        }
        $scope.addEditRole = function(){
            // var toggled = false;

            if ($scope.user.maintenance.filings == undefined) {
                toggled_filings = false;
            } else {
                toggled_filings = true;
            }
            if ($scope.user.maintenance.billings == undefined) {
                toggled_billings = false;
            } else {
                toggled_billings = true;
            } 
            if ($scope.user.maintenance.atms == undefined) {
                toggled_atms = false;
            } else {
                toggled_atms = true;
            }
            if ($scope.user.maintenance.memberships == undefined) {
                toggled_memberships = false;
            } else {
                toggled_memberships = true;
            }  
            if ($scope.user.maintenance.user_managements == undefined) {
                toggled_user_managements = false;
            } else {
                toggled_user_managements = true;
            }

            if ($scope.user.maintenance.settings == undefined) {
                toggled_settings = false;
            } else {
                toggled_settings = true;
            }
           
            // console.log("filings:"+toggled_filings);
            // console.log("billings:"+toggled_billings);
            // console.log("atms:"+toggled_atms);
            // console.log("memberships:"+toggled_memberships);
            // console.log("user_managements:"+toggled_user_managements);
        
           
            var checkerA = inputChecker.textInput(['recId']);
          
            if(checkerA) {

                    swal({
                        title: "Oops!",
                        text: "Please fill out the required fields.",
                        type: "warning",
                        confirmButtonClass: "btn-success",
                        confirmButtonText: "OK",
                        closeOnConfirm: true
                    });
                
            } 
         
            
            else {
                if (toggled_filings || toggled_billings || toggled_atms || toggled_memberships || toggled_user_managements || toggled_settings) {
                    $http({
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        url: baseUrl + '/users/setNewRole',
                        data: {
                            id_key: $scope.user.id_key,
                            current_user_id: $scope.user_info.user_id,
                            role_name: $scope.user.roles.name,
                            role_access_filing: $scope.user.maintenance.filings = ($scope.user.id_key == null || $scope.user.id_key == "") ? $scope.user.maintenance.filings : $scope.user.edit_roles.filing,
                            role_access_biling: $scope.user.maintenance.billings = ($scope.user.id_key == null || $scope.user.id_key == "") ? $scope.user.maintenance.billings : $scope.user.edit_roles.billing,
                            role_access_atm: $scope.user.maintenance.atms = ($scope.user.id_key == null || $scope.user.id_key == "") ? $scope.user.maintenance.atms : $scope.user.edit_roles.atm,
                            role_access_settings: $scope.user.maintenance.settings = ($scope.user.id_key == null || $scope.user.id_key == "") ? $scope.user.maintenance.settings : $scope.user.edit_roles.settings,
                            role_access_memberships: $scope.user.maintenance.memberships = ($scope.user.id_key == null || $scope.user.id_key == "") ? $scope.user.maintenance.memberships : $scope.user.edit_roles.membership,
                            role_access_user_managements: $scope.user.maintenance.user_managements = ($scope.user.id_key == null || $scope.user.id_key == "") ? $scope.user.maintenance.user_managements : $scope.user.edit_roles.user_management,
                        }
                    }).then(function successCallback(response) {
                        if (response.data.statusCode == 200) {
                            // growlService.growl(response.data.message,'success');
                            growlService.growl(response.data.devMessage, "success");
                            // swal({
                            //         title: "User role was successfully added.",
                            //         type: "success",
                            //         showCancelButton: false,
                            //         closeOnConfirm: true
                            // },
                            // function(onConfirm) {
                            //     if(onConfirm){
                            //
                            //     }
                            // });
                            $scope.getUserRoleList();
                            $scope.form = {};
                            $rootScope.cancel();
                            $scope.user.maintenance = {};
                            $scope.user.roles.name = "";

                        } else if (response.data.statusCode == 400) {
                            swal({

                                title: "Oops!",
                                text: "Role name already exists.",
                                type: "warning",
                                confirmButtonClass: "btn-success",
                                confirmButtonText: "OK",
                                closeOnConfirm: true
                            });
                            return;
                        }
                        // if(response.data.statusCode == 200){
                        //     $scope.system_modules_list = response.data.devMessage;
                        // }
                    }, function errorCallback(response) {
                    });
                } else {
                    swal({
                        title: "Oops!",
                        text: "Please toggle at least one option.",
                        type: "warning",
                        confirmButtonClass: "btn-success",
                        confirmButtonText: "OK",
                        closeOnConfirm: true
                    });
                    return;
                }
            
           
    

          }
        }

        $scope.getUserRoleList = function(){
            $scope.rolesTable = new NgTableParams({
                sorting : {
                    fullName    :   'asc'   // Column name and sort type
                },
                    page    :   1,          // Page number
                    count   :   10,         // Record count per page
             },{
                total   :   0,              //  Initial total record count
                getData : function($defer, params) {
                    var page   =    params.page();
                    var count  =    params.count();

                    $http({
                        method  : 'POST',
                        url: baseUrl + '/users/getUserRoles',
                        headers: {
                            'Content-Type'  : 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        data    :  JSON.stringify({
                            page         : page,
                            count        : count,
                            search       : $scope.user_role_search == undefined ? "" : $scope.user_role_search,
                        })
                    })
                    .then(function successCallback(response) {
                        $scope.userRoleList = response.data.devMessage;
                        $scope.rolesTable.total(response.data.totalItems);
                        $defer.resolve($scope.userRoleList);

                        if (response.data.devMessage == null || response.data.devMessage.length <= 0) {
                          if($scope.rolesTable.page() > 1){
                            $scope.lastData = $scope.rolesTable.page() - 1;
                            $rootScope.customPager($scope, 'rolesTable', true);
                          }
                        }
                        else {
                          $rootScope.customPager($scope, 'rolesTable');
                        }
                    },function errorCallback(response){

                    });
                }
            });
        }

        $scope.clearFields = function() {
            $scope.form = {};
            $scope.cancel();
        }

        $scope.clearSearchFields = function(){
            $scope.user_role_search = "";
        }

        $scope.deleteUserRoles = function(key){
            swal({
                title: "Are you sure?",
                text: "You're about to delete this record. There's no other way to retrieve this after deleting.",
                type: "warning",
                showConfirmButton: true,
                confirmButtonText: "Yes, Delete It!",
                confirmButtonClass: "btn-success",
                showCancelButton: true,
                cancelButtonText: "Cancel",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function(onConfirm) {
                if(onConfirm){
                    $http({
                        method: "POST",
                        url: baseUrl + "/users/deleteUserRole",
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        data: {
                            id: parseInt(key),
                        }
                    })
                    .then(function successCallback(response) {
                        var data = response.data;
                        if(data.statusCode == 200){
                            $scope.getUserRoleList();
                            growlService.growl("Record successfully deleted","success");
                        } else {
                            growlService.growl(data.devMessage,"warning");
                        }

                    },function errorCallback(response){

                    });
              }else {
                return;
              }
          });
        }

        $scope.roleAccessAssign = function(data, key) {
            // console.log("cha");
            
            var i   = data.id;
            
            if($scope.user.id_key == undefined || $scope.user.id_key == null || $scope.user.id_key == "") {
                if(i == 1) {
                    $scope.user.maintenance.filings[key].module_id      = key;
                    $scope.user.maintenance.filings.head_module_id      = i;

                    if (!$scope.user.maintenance.filings[key].view) {
                        $scope.user.maintenance.filings[key].delete = false;
                        $scope.user.maintenance.filings[key].import = false;
                    }
                }
                else if(i == 2) {
                    $scope.user.maintenance.billings[key].module_id      = key;
                    $scope.user.maintenance.billings.head_module_id      = i;

                    if (!$scope.user.maintenance.billings[key].view) {
                        $scope.user.maintenance.billings[key].export = false;
                    }
                   
                }
                else if(i == 3) {
                    $scope.user.maintenance.atms[key].module_id      = key;
                    $scope.user.maintenance.atms.head_module_id      = i;

                    if (!$scope.user.maintenance.atms[key].view) {
                        $scope.user.maintenance.atms[key].pin = false;
                        $scope.user.maintenance.atms[key].export = false;
                    }
                    
                }
                else if(i == 4) {
                    $scope.user.maintenance.settings[key].module_id      = key;
                    $scope.user.maintenance.settings.head_module_id      = i;

                    if (!$scope.user.maintenance.settings[key].view) {
                        $scope.user.maintenance.settings[key].add = false;
                        $scope.user.maintenance.settings[key].edit = false;
                        $scope.user.maintenance.settings[key].delete = false;
                    }
                    
                }
                else if(i == 5) {
                    $scope.user.maintenance.memberships[key].module_id      = key;
                    $scope.user.maintenance.memberships.head_module_id      = i;
                    
                }
                else if(i == 6) {
                    $scope.user.maintenance.user_managements[key].module_id      = key;
                    $scope.user.maintenance.user_managements.head_module_id      = i;

                    if (!$scope.user.maintenance.user_managements[key].view) {
                        $scope.user.maintenance.user_managements[key].add = false;
                        $scope.user.maintenance.user_managements[key].edit = false;
                        $scope.user.maintenance.user_managements[key].delete = false;
                    }
                    
                }
            }
            else {
                // Filing
                $scope.toggled = false;
                var filing = [];
                for(var f = 0; f < 7; f++) {
                   
                    
                    var obj = {
                        "module_id"     : (f + 1),
                        "delete"        : angular.element("#FilingDelete_"+(f + 1))[0].checked,
                        "view"          : angular.element("#FilingView_"+(f + 1))[0].checked,
                        "import"        : angular.element("#FilingImport_"+(f + 1))[0].checked,
                    }
                    filing.push(obj);
                   
                }

                //Billing
                var billing = [];
                for(var i = 7; i < 8; i++) {
                    var obj = {
                        "module_id"     : 8,
                        "view"          : angular.element("#BillingView_"+(i + 1))[0].checked,
                        "export"        : angular.element("#BillingExport_"+(i + 1))[0].checked,
                    }
                    billing.push(obj);
                }

                var atm = [];
                for(var i = 9; i < 10; i++) {
                    var obj = {
                       "module_id"      : 9,
                       "view"           : angular.element("#ATMAdd_"+(i))[0].checked,
                       "pin"            : angular.element("#PinView_"+(i))[0].checked,
                       "export"         : angular.element("#ATMExport_"+(i))[0].checked,
                    }
                    atm.push(obj);
                }

                var settings = [];
                for(var i = 10; i < 11; i++) {
                    var obj = {
                       "module_id"  : 10,
                       "add"        : angular.element("#SettingsAdd_"+(i))[0].checked,
                       "edit"       : angular.element("#SettingsEdit_"+(i))[0].checked,
                       "delete"     : angular.element("#SettingsDelete_"+(i))[0].checked,
                       "view"       : angular.element("#SettingsView_"+(i))[0].checked,
                    }
                    settings.push(obj);
                }

                var membership = [];
                for(var i = 11; i < 12; i++) {
                    var obj = {
                       "module_id"  : 11,
                       "view"       : angular.element("#MembershipView_"+(i))[0].checked,
                    }
                    membership.push(obj);
                }

                var user_management = [];
                for(var i = 12; i < 13; i++) {
                    var obj = {
                       "module_id"     : 12,
                       "add"        : angular.element("#UserManagementAdd_"+(i))[0].checked,
                       "edit"       : angular.element("#UserManagementEdit_"+(i))[0].checked,
                       "delete"     : angular.element("#UserManagementDelete_"+(i))[0].checked,
                       "view"       : angular.element("#UserManagementView_"+(i))[0].checked,
                    }
                    user_management.push(obj);
                }
                var roles = {
                    "filing"                : filing,
                    "billing"               : billing,
                    "atm"                   : atm,
                    "settings"              : settings,
                    "membership"            : membership,
                    "user_management"       : user_management,
                }
                $scope.user.edit_roles = roles;
            }
        }

        $scope.getUserRoleSpecific = function(key) {
            $http({
                method: "POST",
                url: baseUrl + "/users/getSpecificUserRole",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                data: {
                    id: parseInt(key),
                }
            })
            .then(function successCallback(response) {
                if(response.data.statusCode == 200){
                    setTimeout(function() {
                        $scope.user.list = response.data.devMessage;
                    }, 100);
                } else {
                }

            },function errorCallback(response){

            });
        }

        //I read the getUserRoleSpecific() and I don't get some of it.
        //So I decided to create my own function. Feel free to modify it.
        $scope.getRoleSpecific = function(key) {
            return new Promise(function(resolve, reject){
                $http({
                    method: "POST",
                    url: baseUrl + "/users/get/roles",
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    data: {
                        id: parseInt(key),
                    }
                })
                .then(function successCallback(response) {
                    
                    if(response.data.statusCode == 200) {
                        $scope.user.access.data         = response.data.devMessage.roles;
                        $scope.user.access.filings      = [];
                        $scope.user.access.bilings      = [];
                        $scope.user.access.atms         = [];
                        $scope.user.access.settings     = [];
                        $scope.user.access.memberships  = [];
                        $scope.user.access.user_setup   = [];

                        for(var i = 0; i < $scope.user.access.data.length; i++) {
                            if($scope.user.access.data[i].head_module_id == 1) {
                                $scope.user.access.filings.push($scope.user.access.data[i]);
                            }
                            else if($scope.user.access.data[i].head_module_id == 2) {
                                $scope.user.access.bilings.push($scope.user.access.data[i]);
                            }
                            else if($scope.user.access.data[i].head_module_id == 3) {
                                $scope.user.access.atms.push($scope.user.access.data[i]);
                            }
                            else if($scope.user.access.data[i].head_module_id == 4) {
                                $scope.user.access.settings.push($scope.user.access.data[i]);
                            }
                            else if($scope.user.access.data[i].head_module_id == 5) {
                                $scope.user.access.memberships.push($scope.user.access.data[i]);
                            }
                            else if($scope.user.access.data[i].head_module_id == 6) {
                                $scope.user.access.user_setup.push($scope.user.access.data[i]);
                            }
                        }
                    }
                    resolve(true);

                },function errorCallback(response){
                    reject(true);
                });
            });
        }
    }])

.controller("ModalInstanceCtrl", ["$rootScope", "$scope", "$uibModalInstance", "content",
    function name($rootScope, $scope, $uibModalInstance, content) {
        $rootScope.modalContent = content; { };
        $rootScope.ok = function () { $uibModalInstance.close(); }
        $rootScope.cancel = function () { $uibModalInstance.dismiss('cancel'); }
    }
])
