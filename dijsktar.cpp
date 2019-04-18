#include <bits/stdc++.h>
using namespace std;

void print(int *dis,int v)
        {
          for(int i=0;i<v;i++)
           cout<<dis[i]<<" ";
           cout<<endl;

        }
int mini(int *dis,int *inc,int v){
   int m=INT_MAX,ind=0;
       for(int i=0;i<v;i++)

              {
                if(inc[i]==0 && dis[i]<=m)
                 {
                    ind=i;
                    m=dis[i];
                 }
              }
            return ind;

          }
void dijkstra(int **arr,int *dis,int *inc,int v,int s)
 {
 for(int i=0;i<v;i++)
   dis[i]=INT_MAX;
   dis[s]=0;

  for(int i=0;i<v;i++) 
   {

    int u=mini(dis,inc,v);
     inc[u]=1;
    
      for(int j=0;j<v;j++)
      {
           if(!inc[j] && arr[u][j] && dis[u]!=INT_MAX && (dis[u]+arr[u][j])<dis[j])
              dis[j]=dis[u]+arr[u][j];

      }
   


   }
  print(dis,v);
    

 }

void fun(){
int *dis,*inc,v,**arr;
cin>>v;
arr=new int *[v];
 for(int i=0;i<v;i++)
   arr[i]=new int[v];
dis=new int[v];
inc=new int[v];

for(int i=0;i<v;i++) 
    {
      for(int j=0;j<v;j++)
         cin>>arr[i][j];
    }
int k;
  cin>>k;
dijkstra(arr,dis,inc,v,k);
 }
int main(){
int n;
cin>>n;
for(int i=0;i<n;i++)
  fun();


}
