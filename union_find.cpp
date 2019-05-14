

     #include <bits/stdc++.h>
     using namespace std;
     int root(int *arr,int i)
      {
          while(arr[i]!=i) // find root node of node or index i
          {
              i=arr[i];
          }
          return i;
      }
     void union1(int *arr,int *size,int a,int b)
        {
            int root_A=root(arr,a); //arr of a
            int root_B=root(arr,b); //arr of b
            if(arr[root_A]==arr[root_B]) //if arr of a and b equal then no need of make arr of a of b or vice versa.
            return;
            if(size[root_A]<size[root_B])       
            {
                arr[root_A]=arr[root_B]; //make b as arr of a
                size[root_B]+=size[root_A];  // also add size of a(child of a also become child of b);
                
            }
            else
            {
                arr[root_B]=arr[root_A]; //make a as arr of b
                size[root_A]+=size[root_B]; // also add size of b(child of b also become child of a);
            }
        }
     int main(){
         int v,e;//no of element,e is no  of union operation
         cin>>v>>e;
         int *arr=new int[v+1];
         int *size=new int[v+1];
         for(int i=1;i<=v;i++){
         arr[i]=i;  //represent iteslf arr
         size[i]=1;   //intially no child of each node so size of each is 1.
         }
         for(int i=0;i<e;i++)
           {
               int a,b;
               cin>>a>>b;
               union1(arr,size,a,b);//make union of a,b
                 
           }
         for(int i=1;i<=v;i++)
            {
             cout<<arr[i]<<" ";
            }
            cout<<endl;
         
          for(int i=1;i<=v;i++)
            {
             cout<<size[i]<<" ";
            }
            cout<<endl;
         
     }

 

