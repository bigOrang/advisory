<?php
namespace app\admin\controller;

use app\admin\model\AdvisoryModel;
use app\admin\model\CategoryModel;
use app\admin\model\CategoryUserModel;
use think\Db;
use think\Exception;
use think\facade\Log;
use think\Request;

class Category extends Base
{
    public function index(Request $request)
    {
        if ($request->isPost()) {
            try{
                $limit = $request->param('limit', 10);
                $searchData = $request->param();
                $categoryModel = new CategoryModel();
                $data = $categoryModel->alias("c")->where(function ($query) use ($searchData){
                    //模糊搜索
                    if (isset($searchData['search']) || !empty($searchData['search']))
                        $query->where('c.name', 'like','%'.$searchData['search'].'%');
                })
                    ->field("c.*,GROUP_CONCAT(a.user_name) as user_name")
                    ->leftJoin("t_advisory_category_user a","a.c_id=c.id")
                    ->group("c.id")->paginate($limit);
                $data = json_decode(json_encode($data),true);
            } catch (Exception $exception) {
                Log::error('获取数据错误：'. $exception->getMessage());
                $data = ['total' => 0, 'rows' => []];
            }
            return [
                'total' => $data['total'],
                'rows' => $data['data']
            ];
        }
        return $this->fetch('./category/index');
    }


    public function add(Request $request) {
        if ($request->isPost()) {
            $requestData = $this->validation($request->post(), 'Category');
            Db::startTrans();
            try {
                $userInfo = [];
                $categoryModel = new CategoryModel();
                $categoryUserModel = new CategoryUserModel();
                $c_id = $categoryModel->insertGetId([
                    'name'       => $requestData['name'],
                    'description'=> $requestData['description'],
                    'user_id'    => session("user_id")
                ]);
                foreach ($requestData['principal'] as $v) {
                    $principal = explode("_", $v);
                    $userInfo[] = [
                        'c_id' => $c_id,
                        'user_id' => $principal[0],
                        'user_name' => $principal[1]
                    ];
                }
                $categoryUserModel->insertAll($userInfo);
                // 提交事务
                Db::commit();
                return $this->responseToJson([],'添加成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->responseToJson([],'添加失败'.$e->getMessage() , 201);
            }
        }
        $teacher = $this->getTeacher();
        $this->assign('teacher', $teacher);
        return $this->fetch('./category/add');
    }

    public function update(Request $request)
    {
        if ($request->isPost()) {
            $requestData = $this->validation($request->post(), 'Category');
            Db::startTrans();
            try {
                $userInfo = [];
                $categoryModel = new CategoryModel();
                $categoryUserModel = new CategoryUserModel();
                $categoryModel->where("id", $requestData['id'])->update([
                    'name'=>$requestData['name'],
                    'description'=>$requestData['description'],
                    'user_id'=>  session("user_id"),
                ]);
                $categoryUserModel->where("c_id", $requestData['id'])->delete();
                foreach ($requestData['principal'] as $v) {
                    $principal = explode("_", $v);
                    $userInfo[] = [
                        'c_id' => $requestData['id'],
                        'user_id' => $principal[0],
                        'user_name' => $principal[1]
                    ];
                }
                $categoryUserModel->insertAll($userInfo);
                // 提交事务
                Db::commit();
                return $this->responseToJson([],'编辑成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->responseToJson([],'编辑失败'.$e->getMessage() , 201);
            }
        }
        if ($request->has("id") && !empty($request->param("id"))) {
            $id = $request->param("id");
            $category_model = new CategoryModel();
            $categoryUserModel = new CategoryUserModel();
            $data = $category_model->where("id", $id)->find();
            $principal = $categoryUserModel->where("c_id", $id)->column("user_id");
            $teacher = $this->getTeacher();
            $this->assign('principal', $principal);
            $this->assign("data", json_decode(json_encode($data),true));
            $this->assign('teacher', $teacher);
            return $this->fetch('./category/edit');
        } else {
            return $this->responseToJson([],'相关参数未获取' , 201);
        }
    }


    public function delete(Request $request)
    {
        if ($request->has("ids") && !empty($request->param("ids"))) {
            $ids = $request->param("ids");
            try{
                CategoryModel::destroy($ids);
                AdvisoryModel::destroy(function($query) use ($ids) {
                    $query->whereIn("c_id",$ids);
                });
                return $this->responseToJson([],'删除成功' , 200);
            }catch (Exception $e) {
                return $this->responseToJson([],'删除失败'.$e->getMessage() , 201);
            }
        } else {
            return $this->responseToJson([],'相关参数未获取' , 201);
        }
    }


    public function validation($data, $name)
    {
        $valid = $this->validate($data, $name);
        if (true !== $valid) {
            exit($this->responseToJson([], $valid, 201, false));
        }
        return $data;
    }
}
