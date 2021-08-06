<?php
require __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;

class Tickets
{
    public function getData()
    {

        $uri = '/api/v2/tickets.json?page[size]=100';
       $tickets = $this->fetchData($uri,'tickets');
        $tickets = array_map(function ($tag){
            return array(
                'ticket_id'=>$tag['id'],
                'description'=>$tag['description'],
                'status'=>$tag['status'],
                'priority'=>$tag['priority'],
                'agent_id'=>$tag['assignee_id'],
                'contact_id'=>$tag['requester_id'],
                'group_id'=>$tag['group_id'],
               'organization_id'=>$tag['organization_id']
            );
        },$tickets);
        $refactoredTickets = $this->getDataFromRelatedEntities($tickets);
        $fullTickets = $this->getTicketsWithComments($refactoredTickets);

$csvHeader = $this->getHeaderForCSV($fullTickets);

    $this->putOnCSV($csvHeader,$fullTickets);
    }
    public function fetchData(string $uri, string $entity):array
    {
        $allData = [];

        $client = new Client(
            [
                'base_uri' => 'https://test2163.zendesk.com'
            ]
        );

        do {
            $response = $client->request('GET', $uri, [
                    'headers' => [
                        'Authorization' => 'Basic YXJtYW5leG8yMDEyQGdtYWlsLmNvbS90b2tlbjpnWW5XNnRzeWc5dVloWUQwaVB0dWZhOXh4TVhub2hHUDJOUVd6QTM0'
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            $allData = array_merge($allData, $data[$entity]);
           if (!empty($allData)) {
                $filteredData = $this->filterRows($allData);
            }
            $uri = $data['links']['next'];
            $bool = $data['meta']['has_more'];

        } while ($bool === true);
        return $filteredData;
        }

    public function getDataFromRelatedEntities(array $tickets): array
    {
        $users = $this->fetchData("/api/v2/users.json?page[size]=100", 'users');
        foreach ($users as $item) {
            if ($item['role'] === 'agent') {
                $agents[] = $item;
            }
            if ($item['role'] === 'end-user') {
                $contacts[] = $item;
            }
        }
        $agents = array_map(function ($tag) {
            return array(
                'agent_id' => $tag['id'],
                'agent_name' => $tag['name'],
                'agent_email' => $tag['email']
            );
        }, $agents);
        $contacts = array_map(function ($tag) {
            return array(
                'contact_id' => $tag['id'],
                'contact_name' => $tag['name'],
                'contact_email' => $tag['email']
            );
        }, $contacts);

        $groups = $this->fetchData("/api/v2/groups.json?page[size]=100", 'groups');
        $groups = array_map(function ($tag) {
            return array(
                'group_id' => $tag['id'],
                'group_name' => $tag['name']
            );
        }, $groups);
        $organizations = $this->fetchData('/api/v2/organizations.json?page[size]=100', 'organizations');
        $organizations = array_map(function ($tag) {
            return array(
                'organization_id' => $tag['id'],
                'organization_name' => $tag['name']
            );
        }, $organizations);
        foreach ($tickets as &$ticket) {
            foreach ($groups as $group) {
                if ($ticket['group_id'] === $group['group_id']) {
                    $ticket['group_name'] = $group['group_name'];
                }
            }
            foreach ($agents as $agent) {
                if ($ticket['agent_id'] === $agent['agent_id']) {
                    $ticket['agent_name'] = $agent['agent_name'];
                    $ticket['agent_email'] = $agent['agent_email'];
                }
            }
            foreach ($contacts as $contact) {
                if ($ticket['contact_id'] === $contact['contact_id']) {
                    $ticket['contact_name'] = $contact['contact_name'];
                    $ticket['contact_email'] = $contact['contact_email'];
                }
            }
            foreach ($organizations as $organization) {
                if ($ticket['organization_id'] === $organization['organization_id']) {
                    $ticket['organization_name'] = $organization['organization_name'];
                }
            }

        }
        unset($item);

        return $tickets;
    }
    public function getTicketsWithComments(array $refactoredTickets) {

         foreach ($refactoredTickets as &$rows) {

              $id = $rows['ticket_id'];
              $comments = $this->fetchData("/api/v2/tickets/$id/comments.json?page[size]=100", 'comments');
              foreach ($comments as $element){
                  $strComment = $strComment .','. $element['body'];
                  if ($id === $rows['ticket_id']) {
                      $rows['comments'] .= $element['body'].';';
                  }
              }

              unset($item);


         }
            return $refactoredTickets;
    }

    public function filterRows(array $data): array{

        $fields = [
            'id',
            'description',
            'status',
            'priority',
            'group_id',
            'email',
            'name',
            'role',
            'assignee_id',
            'requester_id',
            'organization_id',
            'body'
        ];
        foreach ($data as  $item){
            foreach ($fields as $field){
                if(isset($item[$field])){
                    $formattedItem[$field] = $item[$field];
                }
            }
            $formattedData[] =$formattedItem;

        }
        return $formattedData;
    }
public function putOnCSV(array $arr,array $arr1){
    $fp = fopen('/var/www/html/public/test1.csv','r+');
    fputcsv($fp,$arr);
              foreach ($arr1 as $fields){
                  fputcsv($fp,$fields,',');
              }
            fclose($fp);
}
public function getHeaderForCSV($tickets) :array{
    foreach ($tickets as $elemen){
        foreach ($elemen as $key=>$elem ){
            $strKeys = $strKeys .','. $key;
        }
    }
    $arrKeys = explode(',',$strKeys);
    $uniqueKeys = array_unique($arrKeys);
    unset($uniqueKeys[0]);
    return $uniqueKeys;
}
}
