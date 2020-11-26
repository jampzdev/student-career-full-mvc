psslai
.controller('UserCtrl', ['$scope','$rootScope','$window','$state', '$http', 'growlService','NgTableParams','createModal', 'inputChecker','modalService','prompt',
    function ($scope,$rootScope,$window,$state, $http, growlService, NgTableParams, createModal, inputChecker,prompt) {

    $scope.form     = {};

    $scope.getUsers = function(){
        $scope.usersTable = new NgTableParams({
            sorting : {
                id    :   'desc',    // Column name and sort type
            },
            page    :   1,          // Page number
            count   :   100,         // Record count per page
            },{
                total   :   0,      //  Initial total record count
                getData : function($defer, params) {
                    var page   =    params.page();
                    var count  =    params.count();

                    $http({
                        method  : 'POST',
                        headers: {
                            'Content-Type'  : 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        url:  baseUrl + '/users/getUsers',
                        data  :   JSON.stringify({
                            page       : page,
                            count      : count,
                            search     : $scope.users_search,
                            orderBy    : params.orderBy()[0]
                        })

                    }).then(function successCallback(response) {
                        $scope.users = response.data.userList == null ? [] : response.data.userList;
                        $scope.usersTable.total(response.data.total);
                        $defer.resolve($scope.users);

                        if ($scope.users == null || $scope.users.length <= 0) {
                          if($scope.usersTable.page() > 1){
                            $scope.lastData = $scope.usersTable.page() - 1;
                            $rootScope.customPager($scope, 'usersTable', true);
                          }
                        }
                        else {
                          $rootScope.customPager($scope, 'usersTable');
                        }
                    }, function errorCallback(response){
                    });
                }
            });
    }

    // $scope.onEditSpecific = function (info) {
    //     info.$edit = true;
    //     temporaryData = angular.copy(info);
    // }

    $scope.editUserForm = function (id) {
        createModal.modalInstances(true, 'm', 'static', false, $scope.modalContent, 'editUserForm', $scope);
        angular.forEach($scope.users, function(val, key){
            if(val.id == id){
                $scope.form.id               = val.id;
                $scope.form.complete_name    = val.complete_name;
                $scope.form.email_address    = val.email_address;
                // $scope.form.user_type_holder = val.user_type;
                $scope.form.user_role        = val.user_role;
            }
        });
        // $scope.form.branch = [];
    };

    $scope.updateUserData = function () {
        if (!$scope.form.complete_name || !$scope.form.email_address || !$scope.form.password || !$scope.form.confirm_password || !$scope.form.user_role_update) {
            swal({
                title: "Please fill out the required fields",
                type: "warning",
                confirmButtonClass: "btn-success",
                confirmButtonText: "OK",
                closeOnConfirm: true
            });
            return;
        }
        if(!angular.equals($scope.form.confirm_password, $scope.form.password)) {
            swal({
                title: "Password did not match",
                type: "warning",
                confirmButtonClass: "btn-success",
                confirmButtonText: "OK",
                closeOnConfirm: true
            });
            return;
        }

        // if (!$scope.form.email_address.includes("@")) {
        //     swal({
        //         title: "Invalid Username",
        //         type: "warning",
        //         confirmButtonClass: "btn-success",
        //         confirmButtonText: "OK",
        //         closeOnConfirm: true
        //     });
        //     return;
        // }


        $http({
                method: "POST",
                url: baseUrl + "/users/updateUserData",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                data: {
                    id              : $scope.form.id,
                    user_name       : $scope.form.complete_name,
                    user_email      : $scope.form.email_address,
                    user_password   : $scope.form.password,
                    // user_type       : $scope.form.user_role,
                    user_role       : $scope.form.user_role_update,
                }
            })
            .then(function sucessCallback(response) {
                // info.$edit = false;

                if(response.data.statusCode == 200){
                    growlService.growl("Record(s) successfully updated","success");
                    $scope.getUsers();
                    $scope.cancel();
                    // swal({
                    //         title: "User was successfully updated.",
                    //         type: "success",
                    //         showCancelButton: false,
                    //         closeOnConfirm: true
                    // },
                    // function(onConfirm) {
                    //     if(onConfirm){
                    //         $scope.form = {};
                    //         $scope.cancel();
                    //     }
                    // });
                }
                else if (response.data.statusCode == 400) {
                    swal({
                        title: "User update failed.",
                        type: "warning",
                        confirmButtonClass: "btn-success",
                        confirmButtonText: "OK",
                        closeOnConfirm: true
                    });
                }

            }, function errorCallback(response) {

            });
    }

    $scope.onDeleteUser = function (key) {
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
                // $state.go('login');
                $http({
                        method: "POST",
                        url: baseUrl + "/users/deleteScpecificUser",
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        data: {
                            id: parseInt(key),
                        }
                    })
                    .then(function successCallback(response) {
                        growlService.growl("Record(s) successfully deleted","success");
                        // setTimeout(function () {
                        //     swal({
                        //         title: "Record has been successfully deleted.",
                        //         type: "success",
                        //         confirmButtonClass: "btn-success",
                        //         confirmButtonText: "OK",
                        //         closeOnConfirm: true
                        //     });
                        // }, 100);

                        $scope.getUsers();

                    },function errorCallback(response){
                        setTimeout(function () {
                            swal({
                                title: response.data.devMessage.devMessage,
                                type: "warning",
                                confirmButtonClass: "btn-success",
                                confirmButtonText: "OK",
                                closeOnConfirm: true
                            });
                        }, 100);
                        //console.log(response.data.devMessage.devMessage);
                        //prompt.error(response.data.devMessage.devMessage);

                    });
            }
        });


    }

    $scope.addUserForm = function (template) {
        $scope.form = {};
        createModal.modalInstances(true, 'm', 'static', false, $scope.modalContent, 'addUserForm', $scope);
        // $scope.form.list = [];
    };

    $scope.addNewUser = function () {
        var checkerContent  = inputChecker.textInput(['completeName', 'userName', 'password', 'confirmPassword']);
        var checkerUserRole = inputChecker.selectInput(['userRole']);

        if(checkerContent || checkerUserRole) {
            swal({
                title: "Oops!",
                text: "Please fill out the required fields.",
                type: "warning",
                confirmButtonClass: "btn-success",
                confirmButtonText: "OK",
                closeOnConfirm: true
            });
            return;
        } else if (!angular.equals($scope.form.confirm_password, $scope.form.password)) {
            swal({
                title: "Password did not match",
                type: "warning",
                confirmButtonClass: "btn-success",
                confirmButtonText: "OK",
                closeOnConfirm: true
            });
            return;
        } if($scope.form.password.length < 8 || $scope.form.confirm_password.length < 8) {
            swal({
                title: "Password/Confirm Password length should be 8 or above.",
                type: "warning",
                confirmButtonClass: "btn-success",
                confirmButtonText: "OK",
                closeOnConfirm: true
            });
            return;
        } else {

            // if(!$scope.form.email_address) {
            //     swal({
            //         title: "Invalid email format.",
            //         type: "warning",
            //         confirmButtonClass: "btn-success",
            //         confirmButtonText: "OK",
            //         closeOnConfirm: true
            //     });

            //     angular.element("#emailAddressContainer").addClass("has-error");
            // }
            // else {
            //     angular.element("#emailAddressContainer").removeClass("has-error");

            // }

            $http({
                method: "POST",
                url: baseUrl + "/users/addNewUser",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                data: {
                    user_name     : $scope.form.complete_name,
                    user_email    : $scope.form.user_name,
                    user_password : $scope.form.password,
                    user_role     : parseInt($scope.form.user_role),
                }
            })
            .then(function sucessCallback(response) {
                if(response.data.statusCode == 200){
                    growlService.growl("Record(s) successfully added","success");
                    $scope.cancel();
                    $scope.getUsers();
                    // swal({
                    //         title: "User was successfully added.",
                    //         type: "success",
                    //         showCancelButton: false,
                    //         closeOnConfirm: true
                    // },
                    // function(onConfirm) {
                    //     if(onConfirm){
                    //         $scope.cancel();
                    //     }
                    // });
                }
                else if(response.data.statusCode == 204) {
                    swal({
                        title: "Oops!",
                        text: response.data.devMessage,
                        type: "warning",
                        confirmButtonClass: "btn-success",
                        confirmButtonText: "OK",
                        closeOnConfirm: true
                    });
                    return;
                }
                else if (response.data.statusCode == 400) {
                    swal({
                        title: "User creation failed.",
                        type: "warning",
                        confirmButtonClass: "btn-success",
                        confirmButtonText: "OK",
                        closeOnConfirm: true
                    });
                }

            }, function errorCallback(response) {

            });
        }
    }

    $scope.getUserDetailsOnCLick = function(){

    }

    $scope.clearFields = function() {
        $scope.form = {};
        $scope.cancel();
    }

    $scope.clearSearch = function() {
        $scope.form = {};
        $scope.users_search = null;
    }

    $scope.getUserRolesDropdown = function() {
        $http({
            method  : 'POST',
            headers: {
                'Content-Type'  : 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            url:  baseUrl + '/users/getUserRolesDropdown',
            data  :   JSON.stringify({

            })

        }).then(function successCallback(response) {
            $scope.userRoles = response.data.userRoleList == null ? [] : response.data.userRoleList;
            $scope.usersTable.total(response.data.total);
        }, function errorCallback(response){
        });
    }


}])
