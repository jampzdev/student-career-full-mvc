psslai
    .run( function ($rootScope,$window,$state,$location,loginModal) {


        $rootScope.$on('$stateChangeStart', function (event,toState, toParams) {
            var requireLogin = toState.auth;
            if (requireLogin && typeof $window.localStorage.currentUser === 'undefined') {
                loginModal()
               .then(function () {
                 return $state.go(toState.name, toParams);
               })
               .catch(function () {
                return $state.go('home');
               });
               return false;
               event.preventDefault();



             /*loginModal()
               .then(function () {
                 return $state.go(toState.name, toParams);
               })
               .catch(function () {
                 return $state.go('home');
               }); */
           }

        });

    })

    .config(function ($stateProvider, $urlRouterProvider, $httpProvider){

        $httpProvider.interceptors.push(function ($timeout, $q, $injector) {
            var loginModal, $http, $state;

            // this trick must be done so that we don't receive
            // `Uncaught Error: [$injector:cdep] Circular dependency found`
            $timeout(function () {
              loginModal = $injector.get('loginModal');
              $http = $injector.get('$http');
              $state = $injector.get('$state');
            });

            return {
              responseError: function (rejection) {
                if (rejection.status !== 401) {
                  return $q.reject(rejection);
                }

                var deferred = $q.defer();

                loginModal()
                  .then(function () {
                    deferred.resolve( $http(rejection.config) );
                  })
                  .catch(function () {
                    $state.go('home');
                    deferred.reject(rejection);
                  });

                return deferred.promise;
              }
            };
        });


        var hasUser = window.localStorage.getItem('currentUser') == null ? '/login' : '/home';
        $urlRouterProvider.otherwise(hasUser);

        $stateProvider


        //------------------------------
        // LOGIN - Ehddver Cabiten
        //------------------------------
        .state ('login', {
            url: '/login',
            title: 'Login',
            auth : true,
            templateUrl: baseUrl +'/index/home',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

            //------------------------------
            // HOME
            //------------------------------

        .state ('home', {
            url: '/home',
            title: 'Home',
            auth : true,
            templateUrl: baseUrl +'/index/home',
            resolve: {
                loadPlugin: function($ocLazyLoad) {
                    return $ocLazyLoad.load ([
                        {
                            name: 'css',
                            insertBefore: '#app-level',
                            files: [
                                'vendors/bower_components/fullcalendar/dist/fullcalendar.min.css',
                            ]
                        },
                        {
                            name: 'vendors',
                            insertBefore: '#app-level-js',
                            files: [
                                'vendors/sparklines/jquery.sparkline.min.js',
                                'vendors/bower_components/jquery.easy-pie-chart/dist/jquery.easypiechart.min.js',
                                'vendors/bower_components/simpleWeather/jquery.simpleWeather.min.js'
                            ]
                        }
                    ])
                }
            }
        })

        .state ('users', {
            url: '/users',
            title: 'Users',
            auth : true,
            templateUrl: baseUrl +'/partial/common',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

        .state ('users.users', {
            url: '/users',
            title: 'Users',
            auth : true,
            templateUrl: baseUrl +'/users/users',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

        .state ('users.roles', {
            url: '/roles',
            title: 'User Roles',
            auth : true,
            templateUrl: baseUrl +'/users/roles',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

        .state ('settings', {
            url: '/settings',
            title: 'Settings',
            auth : true,
            templateUrl: baseUrl +'/partial/common',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

        .state ('settings.category', {
            url: '/category',
            title: 'Category',
            auth : true,
            templateUrl: baseUrl +'/settings/category',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

        .state ('settings.brand', {
            url: '/brand',
            title: 'Brand',
            auth : true,
            templateUrl: baseUrl +'/settings/brand',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

        .state ('settings.customer', {
            url: '/customer',
            title: 'Customer',
            auth : true,
            templateUrl: baseUrl +'/settings/customer',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })


        .state ('settings.unit', {
            url: '/unit',
            title: 'Unit',
            auth : true,
            templateUrl: baseUrl +'/settings/unit',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

        .state ('inventory', {
            url: '/inventory',
            title: 'Inventory',
            auth : true,
            templateUrl: baseUrl +'/partial/common',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

        .state ('inventory.list', {
            url: '/list',
            title: 'Inventory List',
            auth : true,
            templateUrl: baseUrl +'/inventory/list',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

        .state ('transaction', {
            url: '/transaction',
            title: 'Transaction',
            auth : true,
            templateUrl: baseUrl +'/partial/common',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })

        .state ('transaction.pos', {
            url: '/pos',
            title: 'POS',
            auth : true,
            templateUrl: baseUrl +'/transaction/pos',
            resolve: {
                loadPlugin: function($ocLazyLoad) {

                }
            }
        })
    });
