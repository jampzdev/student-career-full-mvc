psslai
.controller('InventoryCtrl', ['createModal', 'helper','$timeout','$scope','$rootScope','$window','$state', '$http', 'growlService','processExcelFile','NgTableParams','modalService',
function (createModal, helper, $timeout, $scope,$rootScope,$window,$state, $http, growlService, processExcelFile, NgTableParams, modalService) {
    $scope.form              = {};
    $scope.inventory         = {};
    $scope.unit_list         = [];

    $scope.OnInit = function(){
        $scope.getUnitList();
        $scope.getCategoryList();
        $scope.getBrandList();
    }

    $scope.addInventoryForm = function (template) {
        $scope.inventory = {};
        createModal.modalInstances(true, 'm', 'static', false, $scope.modalContent, 'addInventoryForm', $scope);
        // $scope.form.list = [];
    };

    $scope.addNewItem = function(){
        var created_by = "";
        if(localStorage.getItem("currentUser")){
            created_by = JSON.parse(localStorage.getItem("currentUser"));
        }

        if($scope.inventory.item_desc == "" ||
            $scope.inventory.qty == "" ||
            $scope.inventory.brand == "" ||
            $scope.inventory.unit == "" ||
            $scope.inventory.category == "" ||
            $scope.inventory.barcode == "" ||
            $scope.inventory.item_name == "" ||
            $scope.inventory.price == ""
        ){
            swal("Oops","Required Field(s) Missing","warning");
        }
        else{
            $http({
                method: "POST",
                url: baseUrl + "/inventory/addNewItem",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                data: {
                    item_desc           : $scope.inventory.item_desc,
                    qty                 : $scope.inventory.qty,
                    id_brand            : $scope.inventory.brand,
                    id_unit             : $scope.inventory.unit,
                    id_category         : $scope.inventory.category,
                    barcode             : $scope.inventory.barcode,
                    item_name           : $scope.inventory.item_name,
                    price               : $scope.inventory.price,
                    created_by          : created_by.user_id
                }
            })
            .then(function sucessCallback(response) {
                if(response.data.statusCode == 200){
                    growlService.growl("Record Saved","success");
                    $scope.cancel();
                    $scope.getInventory();
                }
            }, function errorCallback(response) {

            });
        }
    }

    $scope.getUnitList = function(){
        $http({
            method  : 'POST',
            headers: {
                'Content-Type'  : 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            url:  baseUrl + '/inventory/getUnitList',
            data  :   JSON.stringify({

            })

        }).then(function successCallback(response) {
            $scope.unit_list = response.data.devMessage;
        }, function errorCallback(response){
        });
    }

    $scope.getCategoryList = function(){
        $http({
            method  : 'POST',
            headers: {
                'Content-Type'  : 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            url:  baseUrl + '/inventory/getCategoryList',
            data  :   JSON.stringify({

            })

        }).then(function successCallback(response) {
            $scope.category_list = response.data.devMessage;
        }, function errorCallback(response){
        });
    }

    $scope.getBrandList = function(){
        $http({
            method  : 'POST',
            headers: {
                'Content-Type'  : 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            url:  baseUrl + '/inventory/getBrandList',
            data  :   JSON.stringify({

            })

        }).then(function successCallback(response) {
            $scope.brand_list = response.data.devMessage;
        }, function errorCallback(response){
        });
    }

    $scope.getInventory = function(){
        $scope.inventoryTable = new NgTableParams({
            sorting : {
                id    :   'desc',    // Column name and sort type
            },
            page    :   1,          // Page number
            count   :   10,         // Record count per page
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
                        url:  baseUrl + '/inventory/getInventory',
                        data  :   JSON.stringify({
                            page       : page,
                            count      : count,
                            search     : $scope.inventory_search,
                            orderBy    : params.orderBy()[0]
                        })

                    }).then(function successCallback(response) {
                        $scope.inventoryList = response.data.devMessage.inventoryList == null ? [] : response.data.devMessage.inventoryList;
                        $scope.inventoryTable.total(response.data.total);
                        $defer.resolve($scope.inventoryList);

                        if ($scope.inventoryList == null || $scope.inventoryList.length <= 0) {
                          if($scope.inventoryTable.page() > 1){
                            $scope.lastData = $scope.inventoryTable.page() - 1;
                            $rootScope.customPager($scope, 'inventoryTable', true);
                          }
                        }
                        else {
                          $rootScope.customPager($scope, 'inventoryTable');
                        }
                    }, function errorCallback(response){
                    });
                }
            });
    }

    $scope.onDeleteInventory= function (key) {
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
                        url: baseUrl + "/inventory/deleteSpecificItem",
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        data: {
                            key: parseInt(key),
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

                        $scope.getInventory();

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

    $scope.editInventoryForm = function (data) {
        $scope.inventory.edit_item_desc = data.item_desc;
        $scope.inventory.edit_qty       = data.qty;
        $scope.inventory.edit_brand     = data.id_brand;
        $scope.inventory.edit_unit      = data.id_unit;
        $scope.inventory.edit_category  = data.id_category;
        $scope.inventory.edit_barcode   = data.barcode;
        $scope.inventory.edit_item_name = data.item_name;
        $scope.inventory.edit_price     = data.price;

        $scope.inventory.edit_key           = data.key;
        createModal.modalInstances(true, 'm', 'static', false, $scope.modalContent, 'editInventoryForm', $scope);
        // $scope.form.list = [];
    };

    $scope.updateInventory = function(){
        if($scope.inventory.edit_item_desc == "" ||
            $scope.inventory.edit_qty       == "" ||
            $scope.inventory.edit_brand     == "" ||
            $scope.inventory.edit_unit      == "" ||
            $scope.inventory.edit_category  == "" ||
            $scope.inventory.edit_barcode   == "" ||
            $scope.inventory.edit_item_name == "" ||
            $scope.inventory.edit_price     == ""
        ){
            swal("Oops","Required Field(s) Missing","warning");
        }
        else{
            $http({
                method: "POST",
                url: baseUrl + "/inventory/updateItem",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                data: {
                    key           : parseInt($scope.inventory.edit_key),
    
                    item_desc       :      $scope.inventory.edit_item_desc,
                    qty             :      $scope.inventory.edit_qty,
                    id_brand        :      $scope.inventory.edit_brand,
                    id_unit         :      $scope.inventory.edit_unit,
                    id_category     :      $scope.inventory.edit_category,
                    barcode         :      $scope.inventory.edit_barcode,
                    item_name       :      $scope.inventory.edit_item_name,
                    price           :      $scope.inventory.edit_price

                }
            })
            .then(function successCallback(response) {
                growlService.growl("Record(s) successfully updated","success");
                $scope.cancel();

                $scope.getInventory();

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
    }
}])
