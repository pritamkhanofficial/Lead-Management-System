<?php

namespace App\Controllers;

use App\Models\LeadModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class LeadController extends BaseController
{
    protected $leadModel;

    public function __construct()
    {
        $this->leadModel = new LeadModel();
    }

    public function index()
    {
        $data = [
            'leads' => $this->leadModel->paginate(10),
            'pager' => $this->leadModel->pager,
        ];

        return view('leads/index', $data);
    }

    public function create()
    {
        return view('leads/create');
    }

    public function store()
    {
        $model = new LeadModel();
        $data = $this->request->getPost();
        
        // Validate the data
        $validation = \Config\Services::validation();
        if (!$model->validateData($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        // Insert the lead
        try {
            if ($model->insert($data)) {
                return redirect()->to('/leads')->with('message', 'Lead created successfully');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating lead: ' . $e->getMessage());
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Error creating lead');
    }

    public function import()
    {
        $file = $this->request->getFile('excel_file');
        
        if (!$file->isValid() || $file->getExtension() !== 'xlsx') {
            return redirect()->back()->with('error', 'Please upload a valid Excel file');
        }

        $reader = new XlsxReader();
        $spreadsheet = $reader->load($file->getTempName());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Skip header row
        array_shift($rows);
        
        $successful = $failed = 0;
        
        foreach ($rows as $row) {
            $data = [
                'name' => $row[0],
                'email' => $row[1],
                'phone' => $row[2],
                'status' => $row[3],
            ];
            
            if ($this->leadModel->insert($data)) {
                $successful++;
            } else {
                $failed++;
            }
        }
        
        return redirect()->to('/leads')->with('message', "Import complete. Successful: $successful, Failed: $failed");
    }

    public function export()
    {
        $leads = $this->leadModel->findAll();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers
        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('B1', 'Email');
        $sheet->setCellValue('C1', 'Phone');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Date Added');
        
        // Data
        $row = 2;
        foreach ($leads as $lead) {
            $sheet->setCellValue('A' . $row, $lead['name']);
            $sheet->setCellValue('B' . $row, $lead['email']);
            $sheet->setCellValue('C' . $row, $lead['phone']);
            $sheet->setCellValue('D' . $row, $lead['status']);
            $sheet->setCellValue('E' . $row, $lead['date_added']);
            $row++;
        }
        
        $writer = new Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="leads.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    public function downloadTemplate()
    {
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers with bold formatting
        $headers = ['Name', 'Email', 'Phone', 'Status'];
        foreach (range('A', 'D') as $index => $column) {
            $sheet->setCellValue($column . '1', $headers[$index]);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
            $sheet->getStyle($column . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E2E2E2');
        }
        
        // Add sample data
        $sampleData = [
            ['John Doe', 'john@example.com', '+1234567890', 'New'],
            ['Jane Smith', 'jane@example.com', '+1987654321', 'In Progress'],
            ['Mike Johnson', 'mike@example.com', '+1122334455', 'Closed']
        ];
        
        $row = 2;
        foreach ($sampleData as $data) {
            $sheet->setCellValue('A' . $row, $data[0]);
            $sheet->setCellValue('B' . $row, $data[1]);
            $sheet->setCellValue('C' . $row, $data[2]);
            $sheet->setCellValue('D' . $row, $data[3]);
            $row++;
        }
        
        // Add data validation for Status column
        $validation = $sheet->getCell('D2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"New,In Progress,Closed"');
        
        // Copy validation to other cells in Status column
        for ($i = 3; $i <= 100; $i++) {
            $sheet->getCell('D' . $i)->setDataValidation(clone $validation);
        }
        
        // Add column comments/notes
        $sheet->getComment('A1')->getText()->createTextRun('Required. Maximum 255 characters');
        $sheet->getComment('B1')->getText()->createTextRun('Required. Must be unique and valid email format');
        $sheet->getComment('C1')->getText()->createTextRun('Required. Format: +1234567890 (10-15 digits)');
        $sheet->getComment('D1')->getText()->createTextRun('Required. Select from: New, In Progress, Closed');
        
        // Auto-size columns
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Set sheet title
        $sheet->setTitle('Lead Import Template');
        
        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="lead_import_template.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Save to output
        $writer->save('php://output');
        exit;
    }

    public function ajaxList()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Direct access not allowed']);
        }

        $model = new LeadModel();
        
        // Get request parameters
        $length = $this->request->getPost('length');
        $start = $this->request->getPost('start');
        $search = $this->request->getPost('search')['value'];
        $order = $this->request->getPost('order')[0];
        $columns = ['name', 'email', 'phone', 'status', 'date_added'];
        $orderColumn = $columns[$order['column']];
        $orderDir = $order['dir'];

        // Build query
        $builder = $model->builder();
        
        // Apply search
        if (!empty($search)) {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('email', $search)
                ->orLike('phone', $search)
                ->orLike('status', $search)
                ->groupEnd();
        }

        // Get total records count
        $totalRecords = $builder->countAllResults(false);

        // Apply ordering and pagination
        $builder->orderBy($orderColumn, $orderDir)
                ->limit($length, $start);

        // Get records
        $records = $builder->get()->getResultArray();

        // Format data for DataTables
        $data = [];
        foreach ($records as $record) {
            $data[] = [
                'id' => $record['id'],
                'name' => esc($record['name']),
                'email' => esc($record['email']),
                'phone' => esc($record['phone']),
                'status' => esc($record['status']),
                'date_added' => date('Y-m-d H:i', strtotime($record['date_added']))
            ];
        }

        return $this->response->setJSON([
            'draw' => $this->request->getPost('draw'),
            'recordsTotal' => $model->countAll(),
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    public function edit($id = null)
    {
        if ($id === null) {
            return redirect()->to('/leads')->with('error', 'No lead ID provided');
        }

        $model = new LeadModel();
        $lead = $model->find($id);

        if ($lead === null) {
            return redirect()->to('/leads')->with('error', 'Lead not found');
        }

        return view('leads/edit', ['lead' => $lead]);
    }

    public function update($id = null)
    {
        if ($id === null) {
            return redirect()->to('/leads')->with('error', 'No lead ID provided');
        }

        $model = new LeadModel();
        
        // Check if lead exists
        $lead = $model->find($id);
        if ($lead === null) {
            return redirect()->to('/leads')->with('error', 'Lead not found');
        }

        // Get the data
        $data = $this->request->getPost();
        
        // Validate the data
        $validation = \Config\Services::validation();
        if (!$model->validateData($data, $id)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        // Update the lead
        try {
            if ($model->update($id, $data)) {
                return redirect()->to('/leads')->with('message', 'Lead updated successfully');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating lead: ' . $e->getMessage());
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Error updating lead');
    }

    public function delete($id = null)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        if ($id === null) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'No lead ID provided'
            ]);
        }

        $model = new LeadModel();
        
        try {
            // Check if lead exists
            $lead = $model->find($id);
            if (!$lead) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Lead not found'
                ]);
            }

            // Delete the lead
            if ($model->delete($id)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Lead deleted successfully'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Error deleting lead'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error deleting lead: ' . $e->getMessage()
            ]);
        }
    }
} 