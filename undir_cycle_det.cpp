#include <bits/stdc++.h>
using namespace std;
int v,e,*parent;
list <int> *adj;
int find1(int i)
{
    if(parent[i]==-1)
    return i;
    return find1(parent[i]);
}
void union1(int x,int y)
{
    int set1=find1(x);
    int set2=find1(y);
    if(set1!=set2)
    parent[set1]=set2;
}
int cycle(){
     vector <pair <int,int> > vp;
         for(int i=0;i<v;i++)
         { 
             list <int> :: iterator it;
             for (it=adj[i].begin();it!=adj[i].end();it++)
             {
                 if(i<=(*it))
                 vp.push_back(make_pair (i,*it));  
             }
         }
    for(int i=0;i<e;i++)
    {
       int x=find1(vp[i].first);
       int y=find1(vp[i].second);
        if(x==y)
        return 1;
        union1(x,y);
    }
    return 0;
}
int main(){
        cin>>v>>e;
        adj=new list<int>[v];
        for(int i=0;i<e;i++)
         {
             int a,b;
             cin>>a>>b;
             adj[a].push_back(b);
             adj[b].push_back(a);
         }
         parent=new int[v];
         for(int i=0;i<v;i++)
         parent[i]=-1;
        

        cout<<cycle()<<endl;
}
