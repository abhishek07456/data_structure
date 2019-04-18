#include <bits/stdc++.h>
using namespace std;
class heap
{
    int n;
    int *arr;
    int hs;
    public:
    heap(int n);
    int parent(int i);
    int left(int i);
    int right(int i);
    void insert();
    void print();
    int mini();
    void decr(int,int);
    void heapify(int);
    void extract();
    void delete1();
};
heap :: heap(int n)
{
    this->n=n;
    hs=0;
    arr=new int[n];
}

int heap :: parent(int i)
{
    return (i-1)/2;
}
int heap :: left(int i)
{
    return 2*i+1;
}
int heap :: right(int i)
{
    return 2*i+2;
}
int heap ::mini(){
    return arr[0];
}
void  heap :: insert()
{
    int a;
    cin>>a;
    arr[hs++]=a;
    int i=hs-1;
    while(i!=0 && arr[parent(i)] >arr[i])
    {
        swap(arr[parent(i)],arr[i]);
        i=parent(i);
    }
   
}
void  heap :: print()
{
    for(int i=0;i<hs;i++)
    cout<<arr[i]<<" ";
}
void heap  :: decr(int j,int val)
{
    arr[j]=val;
     int i=hs-1;
    while(i!=0 && arr[parent(i)] >arr[i])
    {
        swap(arr[parent(i)],arr[i]);
        i=parent(i);
    }

}
void  heap ::extract(){
    if (hs <= 0) 
        return ;
    if (hs == 1) 
    { 
        hs--; 
        return;
    } 
  
     
   
    arr[0] = arr[hs-1]; 
    hs--; 
   heapify(0); 
  
   
}
void heap :: heapify(int i){
  int l = left(i); 
    int r = right(i); 
    int s = i; 
    if (l < hs && arr[l] < arr[s]) 
        s  = l; 
    if (r < hs && arr[r] < arr[s]) 
        s  = r; 
    if (s != i) 
    { 
        swap(arr[i], arr[s]); 
        heapify(s); 
    } 
}
void heap :: delete1(){
    int a;
    cin>>a;
    int j=0;
    for(int i=0;i<hs;i++)
    {
        if(arr[i]==a)
        {
            j=i;
            break;
        }
    }
    decr(j,INT_MIN);
    extract();


}

int main(){
    int n;
    cin>>n;
    heap o(n);
    for(int i=0;i<n;i++)
    {
        int a;
        cin>>a;
        switch(a)
        {
            case 1:
            o.insert();
            break;
            case 3:
            cout<<o.mini()<<endl;
            break;
            case 2:
            o.delete1();
            break;
          
        }
    }
          
          
           
}